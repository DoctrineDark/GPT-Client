<?php

namespace App\Entity;

use App\Repository\GptSearchOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GptSearchOptionRepository::class)
 * @ORM\Table(name="gpt_search_options")
 */
class GptSearchOption
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $gptService;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $embeddingModel;

    /**
     * @ORM\Column(type="integer")
     */
    private $vectorSearchResultCount;

    /**
     * @ORM\Column(type="float")
     */
    private $vectorSearchDistanceLimit;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $chatModel;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $temperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxTokens;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $frequencyPenalty;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $presencePenalty;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $systemMessage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $userMessageTemplate;

    /**
     * @ORM\OneToOne(targetEntity=CloudflareIndex::class)
     */
    private $cloudflareIndex;

    /**/

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGptService(): ?string
    {
        return $this->gptService;
    }

    public function setGptService(string $gptService): self
    {
        $this->gptService = $gptService;

        return $this;
    }

    public function getEmbeddingModel(): ?string
    {
        return $this->embeddingModel;
    }

    public function setEmbeddingModel(string $embeddingModel): self
    {
        $this->embeddingModel = $embeddingModel;

        return $this;
    }

    public function getVectorSearchResultCount(): ?int
    {
        return $this->vectorSearchResultCount;
    }

    public function setVectorSearchResultCount(int $vectorSearchResultCount): self
    {
        $this->vectorSearchResultCount = $vectorSearchResultCount;

        return $this;
    }

    public function getVectorSearchDistanceLimit(): ?float
    {
        return $this->vectorSearchDistanceLimit;
    }

    public function setVectorSearchDistanceLimit(float $vectorSearchDistanceLimit): self
    {
        $this->vectorSearchDistanceLimit = $vectorSearchDistanceLimit;

        return $this;
    }

    public function getChatModel(): ?string
    {
        return $this->chatModel;
    }

    public function setChatModel(string $chatModel): self
    {
        $this->chatModel = $chatModel;

        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(?float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function getFrequencyPenalty(): ?float
    {
        return $this->frequencyPenalty;
    }

    public function setFrequencyPenalty(?float $frequencyPenalty): self
    {
        $this->frequencyPenalty = $frequencyPenalty;

        return $this;
    }

    public function getPresencePenalty(): ?float
    {
        return $this->presencePenalty;
    }

    public function setPresencePenalty(?float $presencePenalty): self
    {
        $this->presencePenalty = $presencePenalty;

        return $this;
    }

    public function getSystemMessage(): ?string
    {
        return $this->systemMessage;
    }

    public function setSystemMessage(?string $systemMessage): self
    {
        $this->systemMessage = $systemMessage;

        return $this;
    }

    public function getUserMessageTemplate(): ?string
    {
        return $this->userMessageTemplate;
    }

    public function setUserMessageTemplate(?string $userMessageTemplate): self
    {
        $this->userMessageTemplate = $userMessageTemplate;

        return $this;
    }

    public function getCloudflareIndex(): ?CloudflareIndex
    {
        return $this->cloudflareIndex;
    }

    public function setCloudflareIndex(?CloudflareIndex $cloudflareIndex): self
    {
        $this->cloudflareIndex = $cloudflareIndex;

        return $this;
    }
}
