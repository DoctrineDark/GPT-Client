<?php

namespace App\Controller;

use App\Message\Test;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Gpt\AIService;
use App\Service\Gpt\BGEClient;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\OpenSearch\Client as OpenSearchClient;
use App\Service\VectorSearch\RedisSearcher;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TestController extends AbstractController
{
    private $bus;
    private $client;
    private $tokenizer;
    private $redisSearcher;
    private $redisClient;
    private $vectorizerLogger;

    public function __construct(MessageBusInterface $bus, OpenAIClient $client, Tokenizer $tokenizer, RedisSearcher $redisSearcher, Client $redisClient, LoggerInterface $vectorizerLogger)
    {
        $this->bus = $bus;
        $this->tokenizer = $tokenizer;
        $this->client = $client;
        $this->redisSearcher = $redisSearcher;
        $this->redisClient = $redisClient;
        $this->vectorizerLogger = $vectorizerLogger;
    }

    public function testMessage()
    {
        $this->bus->dispatch(new Test('Test'));

        return new JsonResponse([
            'success' => true
        ]);
    }

    /**
     * @param Request $request
     * @param AIService $AIService
     * @param VectorizeClient $vectorizeClient
     * @return JsonResponse
     * @throws \Exception
     */
    public function testCloudflareEmbeddingStore(Request $request, AIService $AIService, VectorizeClient $vectorizeClient)
    {
        $gptService = 'cloudflare';

        $accountId = $request->request->get('account_id');
        $apiKey = $request->request->get('api_key');
        $model = $request->request->get('model');
        $indexName = $request->request->get('index_name');
        $indexDescription = $request->request->get('index_description');
        $articleId = $request->request->get('article_id');
        $paragraphId = $request->request->get('paragraph_id');
        $title = $request->request->get('title');
        $content = $request->request->get('content');

        $promptEmbeddingRequest = (new GptEmbeddingRequest())
            ->setAccountId($accountId)
            ->setApiKey($apiKey)
            ->setModel($model)
            ->setPrompt($title . ' ' . $content);

        $embedding = $AIService->embedding($gptService, $promptEmbeddingRequest);

        // Set Credentials
        $vectorizeClient->setAccountId($accountId);
        $vectorizeClient->setApiKey($apiKey);

        // Get or Create Index
        $index = $vectorizeClient->getIndex($indexName);
        $index = json_decode($index, 1);

        if (false === $index['success']) {
            $index = $vectorizeClient->createIndex([
                'name' => $indexName,
                'description' => $indexDescription,
                'config' => [
                    'dimensions' => $embedding->dimensions,
                    'metric' => 'cosine'
                ]
            ]);
            $index = json_decode($index, 1);
        }

        // Index Log
        $this->vectorizerLogger->debug('INDEX');
        $this->vectorizerLogger->debug(json_encode($index));

        // Store Embedding
        $vector = $vectorizeClient->insertVectors($index['result']['name'], [
            'vectors' => [
                'id' => 'article_'.$articleId.'_paragraph_'.$paragraphId,
                'values' => $embedding->embedding,
                'metadata' => [
                    'type' => 'paragraph',
                    'article_id' => $articleId,
                    'paragraph_id' => $paragraphId,
                    'title' => $title,
                    'content' => $content
                ]
            ]
        ]);
        $vector = json_decode($vector, 1);

        // Vector Log
        $this->vectorizerLogger->debug('VECTOR');
        $this->vectorizerLogger->debug(json_encode($vector));

        return new JsonResponse([$index, $vector]);
    }


    public function testCloudflareEmbeddingSearch(Request $request, AIService $AIService, VectorizeClient $vectorizeClient)
    {
        $gptService = 'cloudflare';

        $accountId = $request->request->get('account_id');
        $apiKey = $request->request->get('api_key');
        $model = $request->request->get('model');
        $indexName = $request->request->get('index_name');
        $question = $request->request->get('question');
        $topK = $request->request->get('top_k', 3);

        $promptEmbeddingRequest = (new GptEmbeddingRequest())
            ->setAccountId($accountId)
            ->setApiKey($apiKey)
            ->setModel($model)
            ->setPrompt($question);

        $embedding = $AIService->embedding($gptService, $promptEmbeddingRequest);

        // Set Credentials
        $vectorizeClient->setAccountId($accountId);
        $vectorizeClient->setApiKey($apiKey);

        // Query
        $vector = $vectorizeClient->queryVectors($indexName, [
            'vector' => $embedding->embedding,
            'topK' => (int) $topK
        ]);
        $vector = json_decode($vector, 1);

        return new JsonResponse($vector);
    }


    public function testBgeM3Embedding(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $url = $this->getParameter('app.searcher_toolkit_url');
        $path = 'embedding';
        $method = 'POST';

        $response = $httpClient->request($method, $url . $path, [
            'json' => [
                'content' => 'Lorem ipsum dolor////'
            ]
        ]);

        $code = $response->getStatusCode();
        $content = $response->getContent(false);
        $content = json_decode($content, 1);

        return new JsonResponse($content, $code);
    }

    /**
     * @param Request $request
     * @param AIService $AIService
     * @param BGEClient $bgeClient
     * @param OpenSearchClient $openSearchClient
     * @return JsonResponse
     * @throws \Exception
     */
    public function testOpenSearchEmbeddingStore(Request $request, AIService $AIService, BGEClient $bgeClient, OpenSearchClient $openSearchClient)
    {
        $gptService = 'bge';

        $model = 'BAAI/bge-m3';
        $indexName = 'test_index';
        $dimension = 1024;
        $textPropertyName = 'content';
        $embeddingPropertyName = 'embedding';

        $articleId = $request->request->get('article_id');
        $paragraphId = $request->request->get('paragraph_id');
        $title = $request->request->get('title');
        $content = $request->request->get('content');

        $promptEmbeddingRequest = (new GptEmbeddingRequest())
            ->setModel($model)
            ->setPrompt($title . ' ' . $content);

        $embedding = $AIService->embedding($gptService, $promptEmbeddingRequest);

        // Get or Create Index
        $index = $openSearchClient->getIndex($indexName);
        $index = json_decode($index, 1);

        if (isset($index['error'])) {
            $index = $openSearchClient->createIndex($indexName, [
                'settings' => [
                    'index' => [
                        'knn' => true
                    ]
                ],
                'mappings' => [
                    'properties' => [
                        'id' => [
                            'type' => 'keyword'
                        ],
                        $textPropertyName => [
                            'type' => 'text'
                        ],
                        $embeddingPropertyName => [
                            'type' => 'knn_vector',
                            'dimension' => $dimension
                        ],
                        'metadata' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => [
                                    'type' => 'keyword'
                                ],
                                'type' => [
                                    'type' => 'keyword'
                                ],
                                'title' => [
                                    'type' => 'keyword'
                                ],
                                'content' => [
                                    'type' => 'keyword'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            $index = json_decode($index, 1);
        }

        // Index Log
        $this->vectorizerLogger->debug('INDEX');
        $this->vectorizerLogger->debug(json_encode($index));

        // Store Embedding
        $vector = $openSearchClient->insertVector($indexName, [
            'id' => 'paragraph_'.$paragraphId,
            $textPropertyName => $title . ' ' . $content,
            $embeddingPropertyName => $embedding->embedding,
            'metadata' => [
                'id' => $paragraphId,
                'type' => 'paragraph',
                'title' => $title,
                'content' => $content
            ],
        ]);
        $vector = json_decode($vector, 1);

        // Vector Log
        $this->vectorizerLogger->debug('VECTOR');
        $this->vectorizerLogger->debug(json_encode($vector));

        return new JsonResponse([$index, $vector]);
    }

    public function testOpenSearchEmbeddingSearch(Request $request, AIService $AIService, BGEClient $bgeClient, OpenSearchClient $openSearchClient)
    {
        $gptService = 'bge';

        $model = 'BAAI/bge-m3';
        $indexName = 'test_index';
        $question = $request->request->get('question');

        $textPropertyName = 'content';
        $embeddingPropertyName = 'embedding';

        $promptEmbeddingRequest = (new GptEmbeddingRequest())
            ->setModel($model)
            ->setPrompt($question);

        $embedding = $AIService->embedding($gptService, $promptEmbeddingRequest);

        // Query

        // 1. k-NN Search
        /*$parameters = [
            'query' => [
                'knn' => [
                    $embeddingPropertyName => [
                        'vector' => $embedding->embedding,
                        'k' => 5
                    ]
                ]
            ]
        ];*/


        // 2. ANN — HNSW Search
        /*$parameters = [
            'size' => 5,
            'query' => [
                'knn' => [
                    $embeddingPropertyName => [
                        'vector' => $embedding->embedding,
                        'k' => 5,
                    ]
                ]
            ]
        ];*/


        // 3. Hybrid Search
        /*$parameters = [
            'query' => [
                'hybrid' => [
                    'queries' => [
                        [
                            'match' => [
                                $textPropertyName => $question
                            ]
                        ],
                        [
                            'knn' => [
                                $embeddingPropertyName => [
                                    'vector' => $embedding->embedding,
                                    'k' => 5
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];*/
        $parameters = [
            'size' => 3,
            'query' => [
                'bool' => [
                    'should' => [
                        [
                            'match' => [
                                $textPropertyName => [
                                    'query' => $question,
                                    'boost' => 2
                                ]
                            ]
                        ],
                        [
                            'knn' => [
                                $embeddingPropertyName => [
                                    'vector' => $embedding->embedding,
                                    'k' => 5,
                                    'boost' => 1
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $vector = $openSearchClient->search($indexName, $parameters);
        $vector = json_decode($vector, 1);

        return new JsonResponse($vector);
    }
}