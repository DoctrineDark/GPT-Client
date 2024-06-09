<?php

namespace App\Service\Gpt;

use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Response\GptResponse;

class AIService
{
    private $gptServices;

    public function __construct(iterable $gptServices)
    {
        $this->gptServices = $gptServices;
    }

    /**
     * @param string $gptService
     * @param GptQuestionRequest $request
     * @return array
     * @throws \Exception
     */
    public function questionChatRequest(string $gptService, GptQuestionRequest $request) : array
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
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     * @throws \Exception
     */
    public function knowledgebaseChatRequest(string $gptService, GptKnowledgebaseRequest $request) : GptResponse
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
    public function embedding(string $gptService, GptEmbeddingRequest $request) : GptEmbeddingResponse
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
     * @param GptSummarizeRequest $request
     * @return array
     * @throws \Exception
     */
    public function summarizeRequest(string $gptService, GptSummarizeRequest $request) : array
    {
        foreach ($this->gptServices as $gptClient) {
            if ($gptClient->supports($gptService)) {
                return $gptClient->summarizeRequest($request);
            }
        }

        throw new \Exception('GPT Service not found.');
    }
}