<?php

namespace App\Message;

final class Vectorize
{
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

    public function __construct(string $gptService, ?string $gptApiKey, string $gptEmbeddingModel, int $gptMaxTokensPerChunk)
    {
        $this->gptService = $gptService;
        $this->gptApiKey = $gptApiKey;
        $this->gptEmbeddingModel = $gptEmbeddingModel;
        $this->gptMaxTokensPerChunk = $gptMaxTokensPerChunk;
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
    public function getGptEmbeddingModel(): string
    {
        return $this->gptEmbeddingModel;
    }

    /**
     * @return int
     */
    public function getGptMaxTokensPerChunk(): int
    {
        return $this->gptMaxTokensPerChunk;
    }
}
