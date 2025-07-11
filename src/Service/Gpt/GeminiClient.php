<?php


namespace App\Service\Gpt;


use App\Entity\GptRequestHistory;
use App\Repository\GptRequestHistoryRepository;
use App\Service\Gemini\Client;
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
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GeminiClient implements Gpt
{
    public const SERVICE = 'gemini';

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
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
            $promptTokenCount = $this->countTokens($request->model, $options['contents']);

            if($promptTokenCount > $request->tokenLimit) {
                throw new TokenLimitExceededException();
            }

            $optionsCollection[] = $options;

        } else {
            $options = $request->buildOptions(self::SERVICE);
            $optionsCollection[] = $options;
            $promptTokenCount = $this->countTokens($request->model, $options['contents']);

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
                        $promptTokenCount = $this->countTokens($request->model, $options['contents']);

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
            $response = $this->client->generate($request->model, $options);
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
            $systemMessage = null;
            $userMessage = '';
            foreach($options['contents'] as $content) {
                if($content['role'] === 'user') {
                    $userMessage .= implode(PHP_EOL, array_column($content['parts'], 'text'));
                }
            }

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


    /**/

    public function countTokens(string $model, array $contents): int
    {
        $response = $this->client->tokenize($model, ['contents' => $contents]);

        $response = json_decode($response, 1);

        if(array_key_exists('error', $response)) {
            throw new Exception($response['error']['message']);
        }

        return $response['totalTokens'];
    }

    /**
     * @param array $response
     * @return array
     */
    private function rebuildResponse(array $response): array
    {
        $responses = [];

        if(array_key_exists('candidates', $response)) {
            $responses[] = $response;
        } else {
            $responses = $response;
        }

        $res = [];
        $res['datetime'] = date('Y-m-d H:i:s');

        foreach($responses as $response) {
            $candidates = reset($response['candidates']);

            foreach($candidates['content']['parts'] as $part) {
                if(array_key_exists('message', $res)) {
                    $res['message'] .= $part['text'];
                }
                else {
                    $res['message'] = $part['text'];
                }
            }

            if(array_key_exists('usageMetadata', $response)) {
                $res['prompt_tokens'] = (int) $response['usageMetadata']['promptTokenCount'];
                $res['completion_tokens'] = (int) $response['usageMetadata']['candidatesTokenCount'];
                $res['total_tokens'] = (int) $response['usageMetadata']['totalTokenCount'];
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