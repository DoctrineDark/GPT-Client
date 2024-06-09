<?php

namespace App\Entity;

use App\Repository\GptRequestOptionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GptRequestOptionRepository::class)
 * @ORM\Table(name="gpt_request_options")
 */
class GptRequestOption
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
    private $entryTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $listsMessageTemplate;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $checkboxesMessageTemplate;

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

    public function getEntryTemplate(): ?string
    {
        return $this->entryTemplate;
    }

    public function setEntryTemplate(?string $entryTemplate): self
    {
        $this->entryTemplate = $entryTemplate;

        return $this;
    }

    public function getListsMessageTemplate(): ?string
    {
        return $this->listsMessageTemplate;
    }

    public function setListsMessageTemplate(?string $listsMessageTemplate): self
    {
        $this->listsMessageTemplate = $listsMessageTemplate;

        return $this;
    }

    public function getCheckboxesMessageTemplate(): ?string
    {
        return $this->checkboxesMessageTemplate;
    }

    public function setCheckboxesMessageTemplate(?string $checkboxesMessageTemplate): self
    {
        $this->checkboxesMessageTemplate = $checkboxesMessageTemplate;

        return $this;
    }

    /**/

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
