<?php

namespace App\Message;

final class TemplateVectorize
{
    /**
     * @var int
     */
    private $templateId;

    /**
     * @var string
     */
    private $gptService;

    /**
     * @var string
     */
    private $gptApiKey;

    /**
     * @var string
     */
    private $gptEmbeddingModel;

    /**
     * @var int
     */
    private $gptMaxTokensPerChunk;

    public function __construct(int $templateId, string $gptService, ?string $gptApiKey, string $gptEmbeddingModel, int $gptMaxTokensPerChunk)
    {
        $this->gptService = $gptService;
        $this->gptApiKey = $gptApiKey;
        $this->gptEmbeddingModel = $gptEmbeddingModel;
        $this->gptMaxTokensPerChunk = $gptMaxTokensPerChunk;
        $this->templateId = $templateId;
    }

    /**
     * @return int
     */
    public function getTemplateId() : int
    {
        return $this->templateId;
    }

    /**
     * @return string
     */
    public function getGptService() : string
    {
        return $this->gptService;
    }

    /**
     * @return string|null
     */
    public function getGptApiKey() : ?string
    {
        return $this->gptApiKey;
    }

    /**
     * @return string
     */
    public function getGptEmbeddingModel() : string
    {
        return $this->gptEmbeddingModel;
    }

    /**
     * @return int
     */
    public function getGptMaxTokensPerChunk() : int
    {
        return $this->gptMaxTokensPerChunk;
    }
}
