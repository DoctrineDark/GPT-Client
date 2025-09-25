<?php

namespace App\Service\Gpt\Request;

use App\Service\Gpt\Extension\Arrayable;

class GptEmbeddingRequest
{
    use Arrayable;

    /** @var string|null */
    private $accountId;

    /** @var string|null */
    private $apiKey;

    /** @var string|null */
    private $model;

    /** @var string|null */
    private $index;

    /** @var string */
    private $prompt = '';

    /**
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @param string|null $accountId
     * @return self
     */
    public function setAccountId(?string $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string|null $apiKey
     * @return self
     */
    public function setApiKey(?string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * @param string $prompt
     * @return self
     */
    public function setPrompt(string $prompt)
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIndex(): ?string
    {
        return $this->index;
    }

    /**
     * @param string|null $index
     * @return self
     */
    public function setIndex(?string $index)
    {
        $this->index = $index;

        return $this;
    }
}