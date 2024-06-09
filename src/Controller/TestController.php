<?php

namespace App\Controller;

use App\Message\Test;
use App\Service\Gpt\OpenAIClient;
use App\Service\OpenAI\Tokenizer\Tokenizer;
use App\Service\VectorSearch\RedisSearcher;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;

class TestController extends AbstractController
{
    private $bus;
    private $client;
    private $tokenizer;
    private $redisSearcher;
    private $redisClient;

    public function __construct(MessageBusInterface $bus, OpenAIClient $client, Tokenizer $tokenizer, RedisSearcher $redisSearcher, Client $redisClient)
    {
        $this->bus = $bus;
        $this->tokenizer = $tokenizer;
        $this->client = $client;
        $this->redisSearcher = $redisSearcher;
        $this->redisClient = $redisClient;
    }

    public function testMessage()
    {
        $this->bus->dispatch(new Test('Test'));

        return new JsonResponse([
            'success' => true
        ]);
    }
}