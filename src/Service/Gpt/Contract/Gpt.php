<?php

namespace App\Service\Gpt\Contract;

use App\Service\Gpt\Request\GptAssistantRequest;
use App\Service\Gpt\Request\GptSummarizeRequest;
use App\Service\Gpt\Response\GptAssistantResponse;
use App\Service\Gpt\Response\GptEmbeddingResponse;
use App\Service\Gpt\Request\GptKnowledgebaseRequest;
use App\Service\Gpt\Request\GptQuestionRequest;
use App\Service\Gpt\Request\GptEmbeddingRequest;
use App\Service\Gpt\Response\GptResponse;

interface Gpt
{
    /**
     * @param string $name
     * @return bool
     */
    public function supports(string $name) : bool;

    /**
     * @param GptQuestionRequest $request
     * @return array
     */
    public function questionChatRequest(GptQuestionRequest $request): array;

    /**
     * @param GptAssistantRequest $request
     * @return GptAssistantResponse
     */
    public function assistantRequest(GptAssistantRequest $request): GptAssistantResponse;

    /**
     * @param GptKnowledgebaseRequest $request
     * @return GptResponse
     */
    public function knowledgebaseChatRequest(GptKnowledgebaseRequest $request): GptResponse;

    /**
     * @param GptEmbeddingRequest $request
     * @return GptEmbeddingResponse
     */
    public function embedding(GptEmbeddingRequest $request): GptEmbeddingResponse;

    /**
     * @param GptSummarizeRequest $request
     * @return array
     */
    public function summarizeRequest(GptSummarizeRequest $request): array;
}