<?php


namespace App\Service\Gpt;


use App\Entity\GptRequestHistory;
use App\Entity\OpenSearchIndex;
use App\Repository\GptRequestHistoryRepository;
use App\Service\BGE\Client;
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
use App\Service\OpenAI\Client as OpenAIClient;
use App\Service\OpenSearch\Client as OpenSearchClient;
use App\Service\VectorSearch\SearchResponse;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class BGEClient implements Gpt
{
    public const SERVICE = 'bge';

    private $openAIClient;
    private $bgeClient;
    private $openSearchClient;
    private $gptRequestHistoryRepository;
    private $denormalizer;
    private $logger;

    public function __construct(OpenAIClient $openAIClient, Client $bgeClient, OpenSearchClient $openSearchClient, GptRequestHistoryRepository $gptRequestHistoryRepository, DenormalizerInterface $denormalizer, LoggerInterface $logger)
    {
        $this->openAIClient = $openAIClient;
        $this->bgeClient = $bgeClient;
        $this->openSearchClient = $openSearchClient;
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
        $this->logger->debug('KNOWLEDGEBASE REQUEST');
        $this->logger->debug(json_encode($request));

        $this->openAIClient->setApiKey($request->getApiKey());

        $options = $request->buildOptions(\App\Service\Gpt\OpenAIClient::SERVICE);

        $this->logger->debug('OPTIONS');
        $this->logger->debug(json_encode($options));

        $result = $this->openAIClient->chat($options);
        $response = json_decode($result, 1);

        $this->logger->debug('RESULT');
        $this->logger->debug($result);

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
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     * @throws GptServiceException
     * @throws Exception
     */
    public function embedding(GptEmbeddingRequest $request): GptEmbeddingResponse
    {
        $responseJson = $this->bgeClient->embeddings([
            'model' => $request->getModel(),
            'content' => $request->getPrompt()
        ]);
        $response = json_decode($responseJson, 1);

        if (false === $response['success']) {
            throw new GptServiceException(json_encode($response));
        }

        $embeddingResponse = new GptEmbeddingResponse([
            'model' => $request->getModel(),
            'embedding' => $response['embedding'],
            'dimensions' => $response['dimension'],
            'prompt_tokens' => $response['token_count']
        ]);

        return $embeddingResponse;
    }

    /**
     * @param GptEmbeddingRequest $embeddingRequest
     * @param GptEmbeddingResponse $embeddingResponse
     * @param GptSearchRequest $gptSearchRequest
     * @return array
     * @throws GptServiceException
     * @throws Exception
     */
    public function search(GptEmbeddingRequest $embeddingRequest, GptEmbeddingResponse $embeddingResponse, GptSearchRequest $gptSearchRequest): array
    {
        $options = $this->buildSearchOptions($embeddingRequest, $embeddingResponse, $gptSearchRequest);

        $this->logger->debug('EMBEDDING REQUEST');
        $this->logger->debug(json_encode($embeddingRequest->toArray()));

        $this->logger->debug('EMBEDDING RESPONSE');
        $this->logger->debug(json_encode($embeddingResponse->toArray()));

        $this->logger->debug('SEARCH REQUEST');
        $this->logger->debug(json_encode($gptSearchRequest->toArray()));

        $this->logger->debug('SEARCH OPTIONS');
        $this->logger->debug(json_encode($options));

        $vector = $this->openSearchClient->search($embeddingRequest->getIndex(), $options);
        $vector = json_decode($vector, 1);

        $this->logger->debug('VECTOR');
        $this->logger->debug(json_encode($vector));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new Exception('Invalid response');
        }

        if (isset($vector['error'])) {
            throw new GptServiceException($vector['error']['reason']);
        }

        $searchResults = array_map(function($row) {
            return $this->denormalizer->denormalize([
                'id' => $row['_source']['metadata']['id'],
                'type' => $row['_source']['metadata']['type'],
                'score' => $row['_score']
            ], SearchResponse::class);
        }, $vector['hits']['hits']);

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

    /**
     * @param GptEmbeddingRequest $embeddingRequest
     * @param GptEmbeddingResponse $embedding
     * @param GptSearchRequest $gptSearchRequest
     * @return array
     * @throws GptServiceException
     */
    private function buildSearchOptions(GptEmbeddingRequest $embeddingRequest, GptEmbeddingResponse $embedding, GptSearchRequest $gptSearchRequest): array
    {
        switch ($gptSearchRequest->getSearchMode()) {
            case 'knn':
                $options = [
                    'min_score' => $gptSearchRequest->getMinScore(),
                    'size' => $gptSearchRequest->getVectorSearchResultCount(),
                    'query' => [
                        'knn' => [
                            OpenSearchIndex::EMBEDDING_PROPERTY => [
                                'vector' => $embedding->embedding,
                                'k' => $gptSearchRequest->getVectorSearchResultCount()
                            ]
                        ]
                    ],
                ];

                if ($gptSearchRequest->getKnnModeSearchPipeline()) {
                    $options['search_pipeline'] = $gptSearchRequest->getKnnModeSearchPipeline();
                }

                break;

            case 'hybrid':
                $options = [
                    'size' => $gptSearchRequest->getVectorSearchResultCount(),
                    'collapse' => [
                        'field' => 'id'
                    ],
                    'query' => [
                        'function_score' => [
                            'query' => [
                                'hybrid' => [
                                    'queries' => [
                                        [
                                            'match' => [
                                                OpenSearchIndex::TEXT_PROPERTY => [
                                                    'query' => $embeddingRequest->getPrompt(),
                                                    'boost' => $gptSearchRequest->getContentBoost()
                                                ]
                                            ]
                                        ],
                                        [
                                            'knn' => [
                                                OpenSearchIndex::EMBEDDING_PROPERTY => [
                                                    'vector' => $embedding->embedding,
                                                    'k' => $gptSearchRequest->getVectorSearchResultCount(),
                                                    'boost' => $gptSearchRequest->getEmbeddingBoost()
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'boost_mode' => 'replace',
                            'functions' => [
                                [
                                    'script_score' => [
                                        'script' => [
                                            'source' => "
                                                double min = params.min; 
                                                double max = params.max; 
                                                double min_score = params.min_score; 
                                                double normalized = (_score - min) / (max - min); 
                                                return (normalized >= min_score) ? Math.min(normalized, 1.0) : 0;
                                            ",
                                            'params' => [
                                                'min' => 0,
                                                'max' => 10,
                                                'min_score' => $gptSearchRequest->getMinScore()
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ];

                if ($gptSearchRequest->getHybridModeSearchPipeline()) {
                    $options['search_pipeline'] = $gptSearchRequest->getHybridModeSearchPipeline();
                }
                
                break;

            default:
                throw new GptServiceException('Invalid Search Mode.');
        }

        if ($gptSearchRequest->isEnabledReranking()) {
            $options['ext'] = [
                'rerank' => [
                    'rank_window_size' => 10,
                    'query_context' => [
                        'query_text' => $embeddingRequest->getPrompt()
                    ]
                ]
            ];
        }
        
        return $options;
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