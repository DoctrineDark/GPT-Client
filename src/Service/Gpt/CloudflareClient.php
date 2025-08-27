<?php


namespace App\Service\Gpt;


use App\Entity\GptRequestHistory;
use App\Repository\GptRequestHistoryRepository;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Cloudflare\WorkersAI\Client as WorkersAIClient;
use App\Service\Gpt\Contract\Gpt;
use App\Service\Gpt\Exception\GptServiceException;
use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSearchRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptAssistantResponse;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;
use App\Service\VectorSearch\SearchResponse;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CloudflareClient implements Gpt
{
    public const SERVICE = 'cloudflare';

    private $workersAIClient;
    private $vectorizeClient;
    private $gptRequestHistoryRepository;
    private $denormalizer;
    private $logger;

    public function __construct(WorkersAIClient $workersAIClient, VectorizeClient $vectorizeClient, GptRequestHistoryRepository $gptRequestHistoryRepository, DenormalizerInterface $denormalizer, LoggerInterface $logger)
    {
        $this->workersAIClient = $workersAIClient;
        $this->vectorizeClient = $vectorizeClient;
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
     */
    public function questionChatRequest(GptQuestionRequest $request): array
    {
        // TODO: Implement questionChatRequest() method.
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
     * @throws GptServiceException
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function knowledgebaseChatRequest(GptKnowledgebaseRequest $request): GptResponse
    {
        $this->workersAIClient->setAccountId($request->getAccountId());
        $this->workersAIClient->setApiKey($request->getApiKey());

        switch ($request->model) {
            case '@cf/openai/gpt-oss-120b':
            case '@cf/openai/gpt-oss-20b':
                $responseJson = $this->workersAIClient->createModelResponse([
                    'model' => $request->model,
                    'input' => $request->systemMessage . PHP_EOL . $request->userMessage
                ]);
                $response = json_decode($responseJson, 1);

                if (array_key_exists('error', $response)) {
                    throw new GptServiceException($response['error']['message']);
                }

                /** @var GptResponse $gptResponse */
                $gptResponse = $this->denormalizer->denormalize([
                    'model' => $request->model,
                    'datetime' => (new DateTime("now"))->format('Y-m-d H:i:s'),
                    'message' => implode(PHP_EOL, array_merge(
                        ...array_map(function ($item) {
                            return array_column(
                                array_filter($item['content'], function ($c) {
                                    return isset($c['type']) && $c['type'] === 'output_text';
                                }),
                                'text'
                            );
                        }, $response['output'])
                    )),
                    'prompt_tokens' => $response['usage']['prompt_tokens'],
                    'completion_tokens' => $response['usage']['completion_tokens'],
                    'total_tokens' => $response['usage']['total_tokens'],
                ], GptResponse::class);

                break;

            default:
                $responseJson = $this->workersAIClient->runModel($request->model, ['prompt' => $request->systemMessage . PHP_EOL . $request->userMessage]);
                $response = json_decode($responseJson, 1);

                if (false === $response['success']) {
                    throw new GptServiceException(implode(' ', array_column($response['errors'], 'message')));
                }

                /** @var GptResponse $gptResponse */
                $gptResponse = $this->denormalizer->denormalize([
                    'model' => $request->model,
                    'datetime' => (new DateTime("now"))->format('Y-m-d H:i:s'),
                    'message' => $response['result']['response'],
                    'prompt_tokens' => $response['result']['usage']['prompt_tokens'],
                    'completion_tokens' => $response['result']['usage']['completion_tokens'],
                    'total_tokens' => $response['result']['usage']['total_tokens'],
                ], GptResponse::class);
        }

        // Save request
        $this->storeGptRequestHistory($this->gptRequestHistoryRepository, $gptResponse, $request->systemMessage, $request->userMessage);

        return $gptResponse;
    }

    /**
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     * @throws GptServiceException
     * @throws Exception
     */
    public function embedding(GptEmbeddingRequest $request): GptEmbeddingResponse
    {
        $this->workersAIClient->setAccountId($request->getAccountId());
        $this->workersAIClient->setApiKey($request->getApiKey());

        $responseJson = $this->workersAIClient->runModel($request->getModel(), ['text' => $request->getPrompt()]);
        $response = json_decode($responseJson, 1);

        if (false === $response['success']) {
            throw new GptServiceException(implode(' ', array_column($response['errors'], 'message')));
        }

        $embeddingResponse = new GptEmbeddingResponse([
            'model' => $request->getModel(),
            'embedding' => $response['result']['data'][0],
            'dimensions' => $response['result']['shape'][1]
        ]);

        return $embeddingResponse;
    }

    /**
     * @param GptEmbeddingRequest $embeddingRequest
     * @param GptEmbeddingResponse $embeddingResponse
     * @param GptSearchRequest $gptSearchRequest
     * @return array<SearchResponse>
     * @throws GptServiceException
     */
    public function search(GptEmbeddingRequest $embeddingRequest, GptEmbeddingResponse $embeddingResponse, GptSearchRequest $gptSearchRequest): array
    {
        $this->vectorizeClient->setAccountId($embeddingRequest->getAccountId());
        $this->vectorizeClient->setApiKey($embeddingRequest->getApiKey());

        $vector = $this->vectorizeClient->queryVectors($embeddingRequest->getIndex(), [
            'vector' => $embeddingResponse->embedding,
            'topK' => (int) $gptSearchRequest->getVectorSearchResultCount(),
            'returnMetadata' => 'all'
        ]);
        $vector = json_decode($vector, 1);

        $this->logger->debug('VECTOR');
        $this->logger->debug(json_encode($vector));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception('Invalid response');
        }

        if(false === $vector['success']) {
            throw new GptServiceException(implode('. ', array_column($vector['errors'], 'message')));
        }

        $searchResults = array_map(function($row) {
            return $this->denormalizer->denormalize([
                'id' => $row['metadata']['id'],
                'type' => $row['metadata']['type']
            ], SearchResponse::class);
        }, $vector['result']['matches']);

        return $searchResults;
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