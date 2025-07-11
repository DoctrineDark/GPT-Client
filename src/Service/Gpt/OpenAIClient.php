<?php

namespace App\Service\Gpt;

use App\Entity\GptRequestHistory;
use App\Repository\GptRequestHistoryRepository;
use App\Service\Gpt\Contract\Gpt;
use App\Service\Gpt\Exception\GptServiceException;
use App\Service\Gpt\Exception\TokenLimitExceededException;
use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptAssistantResponse;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;
use App\Service\OpenAI\Client;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\RedisSearcher;
use App\Service\VectorSearch\SearchResponse;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OpenAIClient implements Gpt
{
    public const SERVICE = 'openai';

    private $client;
    private $tokenizer;
    private $redisSearcher;
    private $gptRequestHistoryRepository;
    private $denormalizer;
    private $logger;

    public function __construct(Client $client, Tokenizer $tokenizer, RedisSearcher $redisSearcher, GptRequestHistoryRepository $gptRequestHistoryRepository, DenormalizerInterface $denormalizer, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->tokenizer = $tokenizer;
        $this->redisSearcher = $redisSearcher;
        $this->gptRequestHistoryRepository = $gptRequestHistoryRepository;
        $this->denormalizer = $denormalizer;
        $this->logger = $logger;
    }

    /**
     * @param GptQuestionRequest $request
     * @return array
     * @throws ExceptionInterface
     * @throws TokenLimitExceededException
     * @throws Exception
     */
    public function questionChatRequest(GptQuestionRequest $request): array
    {
        $this->client->setApiKey($request->getApiKey());

        $optionsCollection = [];

        if ($request->raw) {
            // Raw OpenAi request validation
            $json = $request->prepareClientMessageTemplate($request->clientMessage, $request->raw);
            $options = json_decode($json, 1);
            $prompt = implode(PHP_EOL, array_column($options['messages'], 'content'));
            $promptTokenCount = $this->tokenizer->count($prompt, $options['model']);

            if($promptTokenCount > $request->tokenLimit) {
                throw new TokenLimitExceededException();
            }

            $optionsCollection[] = $options;

        } else {
            $optionsCollection[] = $request->buildOptions(self::SERVICE);

            $promptTokenCount = $this->tokenizer->count(($request->systemMessage.PHP_EOL.$request->userMessage), $request->model);

            if($promptTokenCount > $request->tokenLimit) {
                $entryTemplate = $request->entryTemplate;
                $messages = array_merge($request->listsMessages, $request->checkboxesMessages);
                $fullClientMessage = $request->fullClientMessage;

                if($messages) {
                    $optionsCollection = [];

                    foreach ($messages as $message) {
                        $userMessage = '';
                        $userMessage .= $entryTemplate;
                        $userMessage .= $message;
                        $userMessage .= $fullClientMessage;

                        $request->setUserMessage($userMessage);

                        $promptTokenCount = $this->tokenizer->count(($request->systemMessage.PHP_EOL.$request->userMessage), $request->model);

                        if($promptTokenCount > $request->tokenLimit) {
                            throw new TokenLimitExceededException();
                        }

                        $optionsCollection[] = $request->buildOptions(self::SERVICE);
                    }
                } else {
                    // if fullClientMessage goes over the token limit
                    throw new TokenLimitExceededException();
                }
            }
        }

        $gptResponses = [];
        foreach ($optionsCollection as $options) {
            $response = $this->chat($this->client, $options);

            if(array_key_exists('error', $response)) {
                throw new GptServiceException($response['error']['message']);
            }

            $res = $this->rebuildResponse($response);

            /** @var GptResponse $gptResponse */
            $gptResponse = $this->denormalizer->denormalize($res, GptResponse::class);

            $gptResponses[] = $gptResponse;

            // Save request
            $systemMessageIndex = array_search('system', array_column($options['messages'], 'role'));
            $userMessageIndex = array_search('user', array_column($options['messages'], 'role'));
            $systemMessage = false !== $systemMessageIndex ? $options['messages'][$systemMessageIndex]['content'] : '';
            $userMessage = false !== $userMessageIndex ? $options['messages'][$userMessageIndex]['content'] : '';

            $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $systemMessage, $userMessage);
        }

        return $gptResponses;
    }

    /**
     * @param string $gptApiKey
     * @return array
     * @throws Exception
     */
    public function assistantList(string $gptApiKey): array
    {
        $this->client->setApiKey($gptApiKey);

        return json_decode($this->client->listAssistants(), 1);
    }

    /**
     * @param GptAssistantRequest $request
     * @return GptAssistantResponse
     * @throws ExceptionInterface
     * @throws GptServiceException
     * @throws Exception
     */
    public function assistantRequest(GptAssistantRequest $request): GptAssistantResponse
    {
        $this->client->setApiKey($request->getApiKey());

        // If Raw is not empty
        if ($request->raw) {
            $options = json_decode($request->raw, 1);
        } else {
            $options = $request->buildThreadAndRunOptions(self::SERVICE);
        }

        // Create Thread & Run
        $run = $this->client->createThreadAndRun($options);
        $run = json_decode($run, 1);

        $this->logger->debug('Run (Attempt 0): '.json_encode($run));

        if (array_key_exists('error', $run)) {
            throw new GptServiceException($run['error']['message']);
        }

        // Poll Run until done
        $maxAttempts = 10;
        $attempt = 1;

        do {
            $delay = 1000000 * $attempt;
            usleep($delay); // Delay 1 sec

            $run = $this->client->retrieveRun($run['thread_id'], $run['id']);
            $run = json_decode($run, 1);

            $this->logger->debug('Run (Attempt '.$attempt.'): '.json_encode($run));

            if (array_key_exists('error', $run)) {
                throw new GptServiceException($run['error']['message']);
            }

            $status = $run['status'] ?? null;

            if (is_null($status) || 'failed' === $status) {
                throw new GptServiceException('Assistant request error: Run failed.');
            }
            elseif ('completed' === $status) {
                break;
            }

            $attempt++;
        } while ($status !== 'completed' && $attempt <= $maxAttempts);

        if ('completed' !== $status) {
            throw new GptServiceException('Assistant request error: Run timed out.');
        }

        $messageList = $this->client->listThreadMessages($run['thread_id']);
        $messageList = json_decode($messageList, 1);

        $this->logger->debug('Messages: '.json_encode($messageList));

        $res = $this->rebuildAssistantResponse($run, $messageList);

        /** @var GptAssistantResponse $gptAssistantResponse */
        $gptAssistantResponse = $this->denormalizer->denormalize($res, GptAssistantResponse::class);

        return $gptAssistantResponse;
    }

    /**
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function knowledgebaseChatRequest(GptKnowledgebaseRequest $request): GptResponse
    {
        $this->client->setApiKey($request->getApiKey());

        $options = $request->buildOptions(self::SERVICE);

        $response = $this->chat($this->client, $options);

        if(array_key_exists('error', $response)) {
            throw new GptServiceException($response['error']['message']);
        }

        $res = $this->rebuildResponse($response);

        /** @var GptResponse $gptResponse */
        $gptResponse = $this->denormalizer->denormalize($res, GptResponse::class);

        // Save request
        $systemMessage = $options['messages'][array_search('system', array_column($options['messages'], 'role'))]['content'];
        $userMessage = $options['messages'][array_search('user', array_column($options['messages'], 'role'))]['content'];
        $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $systemMessage, $userMessage);

        return $gptResponse;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function supports(string $name): bool
    {
        return self::SERVICE === $name;
    }

    /**
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     * @throws ExceptionInterface
     * @throws GptServiceException
     */
    public function embedding(GptEmbeddingRequest $request): GptEmbeddingResponse
    {
        $this->client->setApiKey($request->getApiKey());

        $responseJson = $this->client->embeddings([
            //'api_key' => $request->getApiKey(),
            'model' => $request->getModel(),
            'input' => $request->getPrompt()
        ]);

        $response = json_decode($responseJson, 1);

        if(array_key_exists('error', $response)) {
            throw new GptServiceException($response['error']['message']);
        }

        $embedding = call_user_func_array('array_merge', array_column($response['data'], 'embedding'));
        $dimensions = count($embedding);

        /** @var GptEmbeddingResponse $embeddingResponse */
        $embeddingResponse = $this->denormalizer->denormalize([
            'model' => $response['model'],
            'embedding' => $embedding,
            'dimensions' => $dimensions,
            'prompt_tokens' => $response['usage']['prompt_tokens'],
            'total_tokens' => $response['usage']['total_tokens']
        ], GptEmbeddingResponse::class);

        return $embeddingResponse;
    }

    /**
     * @param GptEmbeddingRequest $embeddingRequest
     * @param GptEmbeddingResponse $embeddingResponse
     * @param int $vectorSearchResultCount
     * @param float $vectorSearchDistanceLimit
     * @return array<SearchResponse>
     */
    public function search(GptEmbeddingRequest $embeddingRequest, GptEmbeddingResponse $embeddingResponse, int $vectorSearchResultCount = 2, float $vectorSearchDistanceLimit = 1.0): array
    {
        $searchResult = $this->redisSearcher->search($embeddingResponse, $vectorSearchResultCount, $vectorSearchDistanceLimit);

        return $searchResult;
    }

    /**
     * @param GptSummarizeRequest $request
     * @return array
     * @throws ExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function summarizeRequest(GptSummarizeRequest $request): array
    {
        $this->client->setApiKey($request->getApiKey());

        $optionsCollection[] = $request->buildOptions(self::SERVICE);
        $promptTokenCount = $this->tokenizer->count($request->userMessage, $request->model);

        if($promptTokenCount > $request->tokenLimit) {
            $messages = $request->messages;

            $optionsCollection = [];
            $request->setMessages([]);
            $promptTokenCount = 0;

            foreach($messages as $message) {
                $request->addMessage($message);
                $request->prepareMainPrompt();

                $promptTokenCount += $this->tokenizer->count($request->userMessage, $request->model);

                if($promptTokenCount > $request->tokenLimit) {
                    $request->unsetLastMessage();

                    if(!empty($request->messages)) {
                        $request->prepareMainPrompt();
                        $optionsCollection[] = $request->buildOptions(self::SERVICE);

                        // Unset messages
                        $request->setMessages([]);
                        $promptTokenCount = 0;
                    }

                    $request->addMessage($message);
                    $request->prepareMainPrompt();

                    $promptTokenCount += $this->tokenizer->count($request->userMessage, $request->model);

                    if($promptTokenCount > $request->tokenLimit) {
                        $chunks = $this->tokenizer->chunk($message, $request->model, $request->tokenLimit);

                        foreach ($chunks as $chunk) {
                            $request->prepareChunkSummarizePrompt($chunk);
                            $optionsCollection[] = $request->buildOptions(self::SERVICE);
                        }
                    }
                    else {
                        $optionsCollection[] = $request->buildOptions(self::SERVICE);
                    }

                    // Unset messages
                    $request->setMessages([]);
                    $promptTokenCount = 0;
                }
            }

            if(!empty($request->messages)) {
                $optionsCollection[] = $request->buildOptions(self::SERVICE);

                // Unset messages
                $request->setMessages([]);
                $promptTokenCount = 0;
            }
        }

        $gptResponses = [];
        foreach ($optionsCollection as $options) {
            $response = $this->chat($this->client, $options);
            //$response = $this->completion($this->client, $options);

            if(array_key_exists('error', $response)) {
                throw new GptServiceException($response['error']['message']);
            }

            $res = $this->rebuildResponse($response);

            /** @var GptResponse $gptResponse */
            $gptResponse = $this->denormalizer->denormalize($res, GptResponse::class);

            $gptResponses[] = $gptResponse;

            // Save request
            /*
            $systemMessage = null;
            $userMessage = $options['prompt'];
            */
            $systemMessage = $options['messages'][array_search('system', array_column($options['messages'], 'role'))]['content'];
            $userMessage = $options['messages'][array_search('user', array_column($options['messages'], 'role'))]['content'];
            $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $systemMessage, $userMessage);
        }

        // Recursion
        if(count($gptResponses) > 1) {
            foreach($gptResponses as $gptResponse) {
                $request->addMessage($gptResponse->message);
            }
            $request->setMainPromptTemplate($request->summariesSummarizePromptTemplate);
            $request->prepareMainPrompt();

            $gptResponses = $this->summarizeRequest($request);
        }

        return $gptResponses;
    }

    /**
     * @param Client $client
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function chat(Client $client, array $options): array
    {
        $result = $client->chat($options);
        $this->logger->debug($result);
        return json_decode($result, 1);
    }

    /**
     * @param Client $client
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function completion(Client $client, array $options) : array
    {
        return json_decode($client->completion($options), 1);
    }

    /**
     * @param array $response
     * @return array
     */
    private function rebuildResponse(array $response) : array
    {
        $res = [];

        foreach($response as $key => $value) {
            switch($key) {
                case 'choices':
                    $message = '';
                    $i = 0;
                    foreach($value as $row) {
                        $message .= $i > 0 ? PHP_EOL : '';

                        if(isset($row['message']['content'])) {
                            $message .= $row['message']['content'];
                        }
                        elseif(isset($row['text'])) {
                            $message .= $row['text'];
                        }

                        $i++;
                    }
                    $res['message'] = $message;

                    break;
                case 'created':
                    $res['datetime'] = date('Y-m-d H:i:s', $value);

                    break;
                case 'usage':
                    $res['completion_tokens'] = $value['completion_tokens'];
                    $res['prompt_tokens'] = $value['prompt_tokens'];
                    $res['total_tokens'] = $value['total_tokens'];

                    break;
                default:
                    $res[$key] = $value;
            }
        }

        return $res;
    }

    private function rebuildAssistantResponse(array $run, array $messageList): array
    {
        $lastMessage = isset($messageList['data']) ? reset($messageList['data']) : [];

        $this->logger->debug('Last mess: ' . json_encode($lastMessage));

        $content = '';
        $i = 0;
        foreach (array_reverse($messageList['data']) as $row) {
            if('assistant' === $row['role']) {
                foreach ($row['content'] as $item) {
                    $content .= $i > 0 ? PHP_EOL : '';
                    $content .= $item['text']['value'] ?? '';

                    $i++;
                }
            }
        }

        // Remove references from response message
        $removePattern = function (string $content, string $pattern = '/【.*?】/'): string {
            return preg_replace($pattern, '', $content);
        };
        $content = $removePattern($content);
        //

        return [
            'id' => $lastMessage['id'] ?? null,
            'assistant_id' => $lastMessage['assistant_id'] ?? null,
            'thread_id' => $lastMessage['thread_id'] ?? null,
            'run_id' => $lastMessage['run_id'] ?? null,
            'model' => $run['model'] ?? null,
            'datetime' => isset($lastMessage['created_at']) ? date('Y-m-d H:i:s', $lastMessage['created_at']) : null,
            'message' => $content,
            'object' => $lastMessage['object'] ?? null,
            'prompt_tokens' => $run['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $run['usage']['completion_tokens'] ?? null,
            'total_tokens' => $run['usage']['total_tokens'] ?? null
        ];
    }

    /**
     * @param GptRequestHistoryRepository $gptRequestHistoryRepository
     * @param GptResponse $gptResponse
     * @param string $systemMessage
     * @param string $userMessage
     * @return GptRequestHistory
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    private function storeGptRequestHistory(GptRequestHistoryRepository $gptRequestHistoryRepository, GptResponse $gptResponse, ?string $systemMessage, ?string $userMessage)
    {
        $gptRequestHistory = new GptRequestHistory();

        $gptRequestHistory->setModel($gptResponse->model);
        $gptRequestHistory->setDatetime(new DateTime($gptResponse->datetime));
        $gptRequestHistory->setSystemMessage($systemMessage);
        $gptRequestHistory->setUserMessage($userMessage);
        $gptRequestHistory->setAssistantMessage($gptResponse->message);
        $gptRequestHistory->setPromptTokens($gptResponse->prompt_tokens);
        $gptRequestHistory->setCompletionTokens($gptResponse->completion_tokens);
        $gptRequestHistory->setTotalTokens($gptResponse->total_tokens);

        $gptRequestHistoryRepository->add($gptRequestHistory);

        return $gptRequestHistory;
    }
}