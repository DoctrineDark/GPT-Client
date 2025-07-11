<?php

namespace App\Message;

final class Vectorize
{
    /**
     * @var string
     */
    private $gptService;

    /**
     * @var string|null
     */
    private $accountId;

    /**
     * @var string
     */
    private $gptApiKey;

    /**
     * @var string
     */
    private $gptEmbeddingModel;

    /**
     * @var string|null
     */
    private $index;

    /**
     * @var int
     */
    private $gptMaxTokensPerChunk;

    public function __construct(string $gptService, ?string $accountId, ?string $gptApiKey, string $gptEmbeddingModel, ?string $index, int $gptMaxTokensPerChunk)
    {
        $this->gptService = $gptService;
        $this->accountId = $accountId;
        $this->gptApiKey = $gptApiKey;
        $this->gptEmbeddingModel = $gptEmbeddingModel;
        $this->index = $index;
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
    public function getAccountId(): ?string
    {
        return $this->accountId;
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

    /**
     * @return string
     */
    public function getIndex(): ?string
    {
        return $this->index;
    }
}
