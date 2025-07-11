<?php

namespace App\Controller;

use App\Message\Test;
use App\Service\Cloudflare\Vectorize\Client as VectorizeClient;
use App\Service\Gpt\AIService;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\RedisSearcher;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

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
}