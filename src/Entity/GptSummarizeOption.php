<?php

namespace App\Entity;

use App\Repository\GptSummarizeOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GptSummarizeOptionRepository::class)
 * @ORM\Table(name="gpt_summarize_options")
 */
class GptSummarizeOption
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
    private $model;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $temperature;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxTokens;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $promptTokenLimit;

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
    private $mainPromptTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $chunkSummarizePromptTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $summariesSummarizePromptTemplate;

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

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

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

    public function getPromptTokenLimit(): ?int
    {
        return $this->promptTokenLimit;
    }

    public function setPromptTokenLimit(?int $promptTokenLimit): self
    {
        $this->promptTokenLimit = $promptTokenLimit;

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

    public function getMainPromptTemplate(): ?string
    {
        return $this->mainPromptTemplate;
    }

    public function setMainPromptTemplate(?string $mainPromptTemplate): self
    {
        $this->mainPromptTemplate = $mainPromptTemplate;

        return $this;
    }

    public function getChunkSummarizePromptTemplate(): ?string
    {
        return $this->chunkSummarizePromptTemplate;
    }

    public function setChunkSummarizePromptTemplate(?string $chunkSummarizePromptTemplate): self
    {
        $this->chunkSummarizePromptTemplate = $chunkSummarizePromptTemplate;

        return $this;
    }

    public function getSummariesSummarizePromptTemplate(): ?string
    {
        return $this->summariesSummarizePromptTemplate;
    }

    public function setSummariesSummarizePromptTemplate(?string $summariesSummarizePromptTemplate): self
    {
        $this->summariesSummarizePromptTemplate = $summariesSummarizePromptTemplate;

        return $this;
    }
}
