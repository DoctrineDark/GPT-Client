<?php

namespace App\Message;

final class ArticleVectorize
{
    /**
     * @var int
     */
    private $articleId;

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

    public function __construct(int $articleId, string $gptService, ?string $gptApiKey, string $gptEmbeddingModel, int $gptMaxTokensPerChunk)
    {
        $this->gptService = $gptService;
        $this->gptApiKey = $gptApiKey;
        $this->gptEmbeddingModel = $gptEmbeddingModel;
        $this->gptMaxTokensPerChunk = $gptMaxTokensPerChunk;
        $this->articleId = $articleId;
    }

    /**
     * @return int
     */
    public function getArticleId() : int
    {
        return $this->articleId;
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
