<?php


namespace App\Service\Gpt\Request;


use App\Service\Gpt\GeminiClient;
use App\Service\Gpt\OpenAIClient;
use App\Service\Gpt\YandexGptClient;

class GptAssistantRequest extends GptRequest
{
    private $assistantId;
    private $instructions;
    private $clientMessage;
    private $clientMessageTemplate;

    public function __construct()
    {
        $this->clientMessageTemplate = $this->defaultClientMessageTemplate();
    }

    /**
     * @return string
     */
    public function getAssistantId(): string
    {
        return $this->assistantId;
    }

    /**
     * @param string $assistantId
     * @return GptAssistantRequest
     */
    public function setAssistantId(string $assistantId): self
    {
        $this->assistantId = $assistantId;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * @return string
     */
    public function getClientMessage(): string
    {
        return $this->clientMessage;
    }

    /**
     * @param string $instructions
     * @return GptAssistantRequest
     */
    public function setInstructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @param string $message
     * @return GptAssistantRequest
     */
    public function setClientMessage(string $message): self
    {
        $this->clientMessage = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getClientMessageTemplate(): string
    {
        return $this->clientMessageTemplate;
    }

    /**
     * @param string|null $template
     * @return GptAssistantRequest
     */
    public function setClientMessageTemplate(string $template) : self
    {
        $this->clientMessageTemplate = $template;

        return $this;
    }

    /**
     * @param string $gptService
     * @return array
     */
    public function buildThreadAndRunOptions(string $gptService): array
    {
        $options = [];

        switch ($gptService) {
            case OpenAIClient::SERVICE:
                $options = [
                    'assistant_id' => $this->assistantId,
                    'thread' => [
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $this->userMessage,
                            ]
                        ]
                    ]
                ];

                if ($this->model) { $options['model'] = $this->model; }
                if ($this->instructions) { $options['instructions'] = $this->instructions; }
                if ($this->tokenLimit) { $options['max_prompt_tokens'] = $this->tokenLimit; }
                if ($this->maxTokens) { $options['max_completion_tokens'] = $this->maxTokens; }

                break;

            case YandexGptClient::SERVICE:
                break;

            case GeminiClient::SERVICE:
                break;
        }

        return $options;
    }

    /**
     * @return GptAssistantRequest
     */
    public function preparePrompt(): self
    {
        $this->userMessage = $this->bindVariables(
            $this->clientMessageTemplate,
            ['user_message' => $this->clientMessage]
        );

        return $this;
    }

    /**
     * @return string
     */
    public function defaultClientMessageTemplate(): string
    {
        return '[user_message]'.PHP_EOL;
    }
}