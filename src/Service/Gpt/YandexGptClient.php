<?php


namespace App\Service\Gpt;


use App\Entity\GptRequestHistory;
use App\Repository\GptRequestHistoryRepository;
use App\Service\Gpt\Contract\Gpt;
use App\Service\Gpt\Exception\TokenLimitExceededException;
use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptAssistantResponse;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\YandexGpt\Client;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class YandexGptClient implements Gpt
{
    public const SERVICE = 'yandex-gpt';

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
     * @param string $name
     * @return bool
     */
    public function supports(string $name): bool
    {
        return self::SERVICE === $name;
    }

    /**
     * @param GptQuestionRequest $request
     * @return array
     * @throws TokenLimitExceededException
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function questionChatRequest(GptQuestionRequest $request): array
    {
        $this->client->setApiKey($request->getApiKey());

        $optionsCollection = [];

        if($request->raw) {
            // Raw OpenAi request validation
            $json = $request->prepareClientMessageTemplate($request->clientMessage, $request->raw);
            $options = json_decode($json, 1);
            $promptTokenCount = count($this->tokenizeCompletion($options)['tokens']);

            if($promptTokenCount > $request->tokenLimit) {
                throw new TokenLimitExceededException();
            }

            $optionsCollection[] = $options;

        } else {
            $options = $request->buildOptions(self::SERVICE);
            $optionsCollection[] = $options;
            $promptTokenCount = count($this->tokenizeCompletion($options)['tokens']);

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
                        $options = $request->buildOptions(self::SERVICE);
                        $promptTokenCount = count($this->tokenizeCompletion($options)['tokens']);

                        if($promptTokenCount > $request->tokenLimit) {
                            throw new TokenLimitExceededException();
                        }

                        $optionsCollection[] = $options;
                    }
                } else {
                    // if fullClientMessage goes over the token limit
                    throw new TokenLimitExceededException();
                }
            }
        }

        $gptResponses = [];
        foreach ($optionsCollection as $options) {
            $response = $this->client->completion($options);
            $response = json_decode($response, 1);

            if(array_key_exists('error', $response)) {
                throw new Exception($response['error']['message']);
            }

            $res = $this->rebuildResponse($response);

            /** @var GptResponse $gptResponse */
            $gptResponse = $this->denormalizer->denormalize($res, GptResponse::class);
            $gptResponse->model = $request->model;

            $gptResponses[] = $gptResponse;

            // Save request
            $systemMessageIndex = array_search('system', array_column($options['messages'], 'role'));
            $userMessageIndex = array_search('user', array_column($options['messages'], 'role'));
            $systemMessage = false !== $systemMessageIndex ? $options['messages'][$systemMessageIndex]['text'] : '';
            $userMessage = false !== $userMessageIndex ? $options['messages'][$userMessageIndex]['text'] : '';

            $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $systemMessage, $userMessage);
        }

        return $gptResponses;
    }

    /**
     * @param GptAssistantRequest $request
     * @return GptAssistantResponse
     */
    public function assistantRequest(GptAssistantRequest $request): GptAssistantResponse
    {
        // TODO: Implement assistantRequest() method.
    }

    /**
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     */
    public function knowledgebaseChatRequest(GptKnowledgebaseRequest $request): GptResponse
    {
        // TODO: Implement knowledgebaseChatRequest() method.
    }

    /**
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     */
    public function embedding(GptEmbeddingRequest $request): GptEmbeddingResponse
    {
        // TODO: Implement embedding() method.
    }

    /**
     * @param GptSummarizeRequest $request
     * @return array
     */
    public function summarizeRequest(GptSummarizeRequest $request): array
    {
        // TODO: Implement summarizeRequest() method.
    }

    /**
     * @param string $folderId
     * @param string $model
     * @param string $prompt
     * @return array
     * @throws Exception
     */
    public function tokenize(string $folderId, string $model, string $prompt): array
    {
        $response = $this->client->tokenize([
            'modelUri' => 'gpt://' . $folderId . '/' . $model,
            'text' => $prompt
        ]);

        $response = json_decode($response, 1);

        if(array_key_exists('error', $response)) {
            throw new Exception($response['error']);
        }

        return $response;
    }

    /**
     * @param array $options
     * @return array
     * @throws Exception
     */
    public function tokenizeCompletion(array $options): array
    {
        $response = $this->client->tokenizeCompletion($options);

        $response = json_decode($response, 1);

        if(array_key_exists('error', $response)) {
            throw new Exception($response['error']);
        }

        return $response;
    }

    /**
     * @param array $response
     * @return array
     */
    private function rebuildResponse(array $response) : array
    {
        $res = [];

        $res['datetime'] = date('Y-m-d H:i:s');

        if(array_key_exists('result', $response)) {
            foreach ($response['result'] as $key => $value) {
                switch ($key) {
                    case 'alternatives':
                        $message = '';
                        $i = 0;
                        foreach($value as $row) {
                            $message .= $i > 0 ? PHP_EOL : '';

                            if(isset($row['message']['text'])) {
                                $message .= $row['message']['text'];
                            }

                            $i++;
                        }
                        $res['message'] = $message;

                            break;

                    case 'usage':
                        $res['prompt_tokens'] = (int) $value['inputTextTokens'];
                        $res['completion_tokens'] = (int) $value['completionTokens'];
                        $res['total_tokens'] = (int) $value['totalTokens'];

                        break;
                }
            }
        }

        return $res;
    }

    /**
     * @param GptRequestHistoryRepository $gptRequestHistoryRepository
     * @param GptResponse $gptResponse
     * @param string|null $systemMessage
     * @param string|null $userMessage
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