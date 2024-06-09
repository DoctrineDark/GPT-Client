<?php

namespace App\Service\Gpt\Request;

class GptEmbeddingRequest
{
    /** @var string|null */
    private $apiKey;

    /** @var string|null */
    private $model = 'text-embedding-3-small';

    /** @var string|null */
    private $prompt;

    /**
     * @return string|null
     */
    public function getApiKey() : ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string|null $apiKey
     * @return $this
     */
    public function setApiKey(?string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPrompt() : ?string
    {
        return $this->prompt;
    }

    /**
     * @param string $prompt
     * @return $this
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
     * @return GptEmbeddingRequest
     */
    public function setModel(string $model) : self
    {
        $this->model = $model;

        return $this;
    }
}