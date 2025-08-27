<?php


namespace App\Service\Gpt\Request;


class GptSearchRequest
{
    /** @var string|null */
    private $searchMode;

    /** @var float|null */
    private $contentBoost;

    /** @var float|null */
    private $embeddingBoost;

    /** @var int|null */
    private $vectorSearchResultCount;

    /** @var float|null */
    private $vectorSearchDistanceLimit;

    /** @var float|null */
    private $minScore;

    /**
     * @return string|null
     */
    public function getSearchMode(): ?string
    {
        return $this->searchMode;
    }

    /**
     * @param string|null $searchMode
     * @return self
     */
    public function setSearchMode(?string $searchMode): self
    {
        $this->searchMode = $searchMode;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getContentBoost(): ?float
    {
        return $this->contentBoost;
    }

    /**
     * @param float|null $contentBoost
     * @return self
     */
    public function setContentBoost(?float $contentBoost): self
    {
        $this->contentBoost = $contentBoost;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getEmbeddingBoost(): ?float
    {
        return $this->embeddingBoost;
    }

    /**
     * @param float|null $embeddingBoost
     * @return self
     */
    public function setEmbeddingBoost(?float $embeddingBoost): self
    {
        $this->embeddingBoost = $embeddingBoost;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getVectorSearchResultCount(): ?int
    {
        return $this->vectorSearchResultCount;
    }

    /**
     * @param int|null $vectorSearchResultCount
     * @return self
     */
    public function setVectorSearchResultCount(?int $vectorSearchResultCount): self
    {
        $this->vectorSearchResultCount = $vectorSearchResultCount;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getVectorSearchDistanceLimit(): ?float
    {
        return $this->vectorSearchDistanceLimit;
    }

    /**
     * @param float|null $vectorSearchDistanceLimit
     * @return self
     */
    public function setVectorSearchDistanceLimit(?float $vectorSearchDistanceLimit): self
    {
        $this->vectorSearchDistanceLimit = $vectorSearchDistanceLimit;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getMinScore(): ?float
    {
        return $this->minScore;
    }

    /**
     * @param float|null $minScore
     * @return self
     */
    public function setMinScore(?float $minScore): self
    {
        $this->minScore = $minScore;

        return $this;
    }
}