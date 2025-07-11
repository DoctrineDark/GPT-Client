<?php

namespace App\Service\Gpt\Request;

use App\Service\Gpt\CloudflareClient;
use App\Service\Gpt\GeminiClient;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\YandexGptClient;

class GptRequest
{
    private $apiKey;
    public $accountId;
    public $folderId;

    public $systemMessage;
    public $userMessage;

    public $raw;
    public $model;
    public $temperature;
    public $maxTokens;
    public $tokenLimit;
    public $frequencyPenalty;
    public $presencePenalty;
    public $responseFormatType = 'text';

    public $responseContentType;
    public $topP;
    public $topK;

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
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * @param string|null $accountId
     * @return $this
     */
    public function setAccountId(?string $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @param string|null $folderId
     * @return $this
     */
    public function setFolderId(?string $folderId)
    {
        $this->folderId = $folderId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFolderId(): ?string
    {
        return $this->folderId;
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
     * @param string $responseFormatType
     * @return $this
     */
    public function setResponseFormatType(string $responseFormatType)
    {
        $this->responseFormatType = $responseFormatType;

        return $this;
    }

    /**
     * @param string $responseContentType
     * @return static
     */
    public function setResponseContentType(string $responseContentType)
    {
        $this->responseContentType = $responseContentType;

        return $this;
    }

    /**
     * @param float $topP
     * @return static
     */
    public function setTopP(float $topP)
    {
        $this->topP = $topP;

        return $this;
    }

    /**
     * @param int $topK
     * @return static
     */
    public function setTopK(int $topK)
    {
        $this->topK = $topK;

        return $this;
    }

    /**
     * @param string $message
     * @param array $variables
     * @return string
     *
     * Variable format: [sample]
     */
    public function bindVariables(string $message, array $variables): string
    {
        $variables = array_combine(array_map(function($key) {return '['.$key.']';}, array_keys($variables)), $variables);

        return strtr($message, $variables);
    }

    /**
     * @param string $gptService
     * @return array
     */
    public function buildOptions(string $gptService): array
    {
        $options = [];

        switch ($gptService) {
            case OpenAIClient::SERVICE:
                $options = [
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
                    'presence_penalty' => $this->presencePenalty,
                    'response_format' => ['type' => $this->responseFormatType],
                ];

                break;

            case YandexGptClient::SERVICE:

                $messages = [];

                if(!empty($this->systemMessage)) {
                    $messages[] = [
                        'role' => 'system',
                        'text' => $this->systemMessage
                    ];
                }

                if(!empty($this->userMessage)) {
                    $messages[] = [
                        'role' => 'user',
                        'text' => $this->userMessage
                    ];
                }

                $options = [
                    'modelUri' => 'gpt://' . $this->folderId . '/' . $this->model,
                    'completionOptions' => [
                        'stream' => false,
                        'temperature' => $this->temperature,
                        'maxTokens' => $this->maxTokens
                    ],
                    'messages' => $messages
                ];

                break;

            case GeminiClient::SERVICE:

                $contents = [];

                if(!empty($this->systemMessage)) {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $this->systemMessage]]
                    ];
                }

                if(!empty($this->userMessage)) {
                    $contents[] = [
                        'role' => 'user',
                        'parts' => [['text' => $this->userMessage]]
                    ];
                }

                $options = [
                    'contents' => $contents,
                    'generationConfig' => [
                        'responseMimeType' => $this->responseContentType,
                        'temperature' => $this->temperature,
                        'maxOutputTokens' => $this->maxTokens,
                        'topP' => $this->topP,
                        'topK' => $this->topK
                    ]
                ];

                break;

            case CloudflareClient::SERVICE:

                //

                break;
        }

        return $options;
    }
}