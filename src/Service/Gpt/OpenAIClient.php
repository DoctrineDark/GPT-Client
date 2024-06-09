<?php

namespace App\Service\Gpt;

use App\Entity\GptRequestHistory;
use App\Repository\GptRequestHistoryRepository;
use App\Service\Gpt\Contract\Gpt;
use App\Service\Gpt\Exception\TokenLimitExceededException;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;
use App\Service\OpenAI\Client;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OpenAIClient implements Gpt
{
    private $client;
    private $tokenizer;
    private $validator;
    private $gptRequestHistoryRepository;
    private $denormalizer;
    private $logger;

    public function __construct(Client $client, Tokenizer $tokenizer, ValidatorInterface $validator, GptRequestHistoryRepository $gptRequestHistoryRepository, DenormalizerInterface $denormalizer, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->tokenizer = $tokenizer;
        $this->validator = $validator;
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
    public function questionChatRequest(GptQuestionRequest $request) : array
    {
        $optionsCollection = [];

        if($request->raw) {
            // Raw OpenAi request validation
            $json = $request->prepareClientMessageTemplate($request->clientMessage, $request->raw);
            $raw = json_decode($json, 1);
            $options = array_merge($raw, ['api_key' => $request->getApiKey()]);
            $prompt = implode(' ', array_column($options['messages'], 'content'));

            $promptTokenCount = $this->tokenizer->count($prompt, $options['model']);

            if($promptTokenCount > $request->tokenLimit) {
                throw new TokenLimitExceededException();
            }

            $optionsCollection[] = $options;

        } else {
            $optionsCollection[] = $request->buildOptions();

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

                        $optionsCollection[] = $request->buildOptions();
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
                throw new Exception($response['error']['message']);
            }

            $res = $this->rebuildResponse($response);

            /** @var GptResponse $gptResponse */
            $gptResponse = $this->denormalizer->denormalize($res, GptResponse::class);

            $gptResponses[] = $gptResponse;

            // Save request
            $systemMessage = $options['messages'][array_search('system', array_column($options['messages'], 'role'))]['content'];
            $userMessage = $options['messages'][array_search('user', array_column($options['messages'], 'role'))]['content'];
            $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $systemMessage, $userMessage);
        }

        return $gptResponses;
    }

    /**
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function knowledgebaseChatRequest(GptKnowledgebaseRequest $request) : GptResponse
    {
        $options = $request->buildOptions();

        $response = $this->chat($this->client, $options);

        if(array_key_exists('error', $response)) {
            throw new Exception($response['error']['message']);
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
    public function supports(string $name) : bool
    {
        return 'openai' === $name;
    }

    /**
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     * @throws ExceptionInterface
     */
    public function embedding(GptEmbeddingRequest $request) : GptEmbeddingResponse
    {
        $responseJson = $this->client->embeddings([
            'api_key' => $request->getApiKey(),
            'model' => $request->getModel(),
            'input' => $request->getPrompt()
        ]);

        $response = json_decode($responseJson, 1);

        if(array_key_exists('error', $response)) {
            throw new Exception($response['error']['message']);
        }

        /** @var GptEmbeddingResponse $embeddingResponse */
        $embeddingResponse = $this->denormalizer->denormalize([
            'model' => $response['model'],
            'embedding' => call_user_func_array('array_merge', array_column($response['data'], 'embedding')),
            'prompt_tokens' => $response['usage']['prompt_tokens'],
            'total_tokens' => $response['usage']['total_tokens']
        ], GptEmbeddingResponse::class);

        return $embeddingResponse;
    }

    /**
     * @param GptSummarizeRequest $request
     * @return array
     * @throws ExceptionInterface
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws Exception
     */
    public function summarizeRequest(GptSummarizeRequest $request) : array
    {
        $optionsCollection[] = $request->buildOptions();
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
                        $optionsCollection[] = $request->buildOptions();

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
                            $optionsCollection[] = $request->buildOptions();
                        }
                    }
                    else {
                        $optionsCollection[] = $request->buildOptions();
                    }

                    // Unset messages
                    $request->setMessages([]);
                    $promptTokenCount = 0;
                }
            }

            if(!empty($request->messages)) {
                $optionsCollection[] = $request->buildOptions();

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
                throw new Exception($response['error']['message']);
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
    public function chat(Client $client, array $options) : array
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

    /**
     * @param GptRequestHistoryRepository $gptRequestHistoryRepository
     * @param GptResponse $gptResponse
     * @param string $systemMessage
     * @param string $userMessage
     * @return GptRequestHistory
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws Exception
     */
    private function storeGptRequestHistory(GptRequestHistoryRepository $gptRequestHistoryRepository, GptResponse $gptResponse, ?string $systemMessage, ?string $userMessage)
    {
        $gptRequestHistory = new GptRequestHistory();

        $gptRequestHistory->setModel($gptResponse->model);
        $gptRequestHistory->setDatetime(new \DateTime($gptResponse->datetime));
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