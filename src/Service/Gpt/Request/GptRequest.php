<?php

namespace App\Service\Gpt\Request;

class GptRequest
{
    private $apiKey;

    public $systemMessage;
    public $userMessage;

    public $raw;
    public $model;
    public $temperature;
    public $maxTokens;
    public $tokenLimit;
    public $frequencyPenalty;
    public $presencePenalty;

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
    public function getApiKey() : ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string $message
     * @return static
     */
    public function setSystemMessage(string $message)
    {
        $this->systemMessage = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return static
     */
    public function setUserMessage(string $message)
    {
        $this->userMessage = $message;

        return $this;
    }

    /**
     * @param string|null $json
     * @return static
     */
    public function setRaw(string $json)
    {
        $this->raw = $json;

        return $this;
    }

    /**
     * @param string $model
     * @return static
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @param float $temperature
     * @return static
     */
    public function setTemperature(float $temperature)
    {
        $this->temperature = $temperature;

        return $this;
    }

    /**
     * @param int $maxTokens
     * @return static
     */
    public function setMaxTokens(int $maxTokens)
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    /**
     * @param int $tokenLimit
     * @return static
     */
    public function setTokenLimit(int $tokenLimit)
    {
        $this->tokenLimit = $tokenLimit;

        return $this;
    }

    /**
     * @param float $freqPenalty
     * @return static
     */
    public function setFrequencyPenalty(float $freqPenalty)
    {
        $this->frequencyPenalty = $freqPenalty;

        return $this;
    }

    /**
     * @param float $presencePenalty
     * @return static
     */
    public function setPresencePenalty(float $presencePenalty)
    {
        $this->presencePenalty = $presencePenalty;

        return $this;
    }

    /**
     * @param string $message
     * @param array $variables
     * @return string
     *
     * Variable format: [sample]
     */
    public function bindVariables(string $message, array $variables) : string
    {
        $variables = array_combine(array_map(function($key) {return '['.$key.']';}, array_keys($variables)), $variables);

        return strtr($message, $variables);
    }

    /**
     * @return array
     */
    public function buildOptions() : array
    {
        return [
            'api_key' => $this->getApiKey(),
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemMessage
                ],
                [
                    'role' => 'user',
                    'content' => $this->userMessage,
                ]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty
        ];
    }
}