<?php

namespace App\Entity;

use App\Repository\GptAssistantOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GptAssistantOptionRepository::class)
 * @ORM\Table(name="gpt_assistant_options")
 */
class GptAssistantOption
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $model;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $temperature;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $topP;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxTokens;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $promptTokenLimit;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $instructions;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $clientMessageTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $rawRequestTemplate;

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

    public function setModel(?string $model): self
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

    public function getTopP(): ?float
    {
        return $this->topP;
    }

    public function setTopP(?float $topP): self
    {
        $this->topP = $topP;

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

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getClientMessageTemplate(): ?string
    {
        return $this->clientMessageTemplate;
    }

    public function setClientMessageTemplate(?string $clientMessageTemplate): self
    {
        $this->clientMessageTemplate = $clientMessageTemplate;

        return $this;
    }

    public function getRawRequestTemplate(): ?string
    {
        return $this->rawRequestTemplate;
    }

    public function setRawRequestTemplate(?string $rawRequestTemplate): self
    {
        $this->rawRequestTemplate = $rawRequestTemplate;

        return $this;
    }
}
