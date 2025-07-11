<?php

namespace App\Service\Gpt;

use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptAssistantResponse;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;
use App\Service\VectorSearch\SearchResponse;

class AIService
{
    private $gptServices;

    public function __construct(iterable $gptServices)
    {
        $this->gptServices = $gptServices;
    }

    public static function list(): array
    {
        return [
            OpenAIClient::SERVICE,
            YandexGptClient::SERVICE,
            GeminiClient::SERVICE,
            CloudflareClient::SERVICE
        ];
    }

    /**
     * @param string $gptService
     * @param GptQuestionRequest $request
     * @return array
     * @throws \Exception
     */
    public function questionChatRequest(string $gptService, GptQuestionRequest $request): array
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->questionChatRequest($request);
            }
        }

        throw new \Exception('GPT Service not found.');
    }


    /**
     * @param string $gptService
     * @param string $gptApiKey
     * @return array
     * @throws \Exception
     */
    public function assistantList(string $gptService, string $gptApiKey): array
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->assistantList($gptApiKey);
            }
        }

        throw new \Exception('GPT Service not found.');
    }

    /**
     * @param string $gptService
     * @param GptAssistantRequest $gptAssistantRequest
     * @return GptAssistantResponse
     * @throws \Exception
     */
    public function assistantRequest(string $gptService, GptAssistantRequest $gptAssistantRequest): GptAssistantResponse
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->assistantRequest($gptAssistantRequest);
            }
        }

        throw new \Exception('GPT Service not found.');
    }

    /**
     * @param string $gptService
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     * @throws \Exception
     */
    public function knowledgebaseChatRequest(string $gptService, GptKnowledgebaseRequest $request): GptResponse
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->knowledgebaseChatRequest($request);
            }
        }

        throw new \Exception('GPT Service not found.');
    }

    /**
     * @param string $gptService
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     * @throws \Exception
     */
    public function embedding(string $gptService, GptEmbeddingRequest $request): GptEmbeddingResponse
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->embedding($request);
            }
        }

        throw new \Exception('GPT Service not found.');
    }

    /**
     * @param string $gptService
     * @param GptEmbeddingRequest $embeddingRequest
     * @param GptEmbeddingResponse $embeddingResponse
     * @param int $vectorSearchResultCount
     * @param float $vectorSearchDistanceLimit
     * @return array<SearchResponse>
     * @throws \Exception
     */
    public function search(string $gptService, GptEmbeddingRequest $embeddingRequest, GptEmbeddingResponse $embeddingResponse, int $vectorSearchResultCount = 2, float $vectorSearchDistanceLimit = 1.0)
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->search($embeddingRequest, $embeddingResponse, $vectorSearchResultCount, $vectorSearchDistanceLimit);
            }
        }

        throw new \Exception('GPT Service not found.');
    }

    /**
     * @param string $gptService
     * @param GptSummarizeRequest $request
     * @return array
     * @throws \Exception
     */
    public function summarizeRequest(string $gptService, GptSummarizeRequest $request): array
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->summarizeRequest($request);
            }
        }

        throw new \Exception('GPT Service not found.');
    }
}