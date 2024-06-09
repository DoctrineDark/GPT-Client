<?php

namespace App\Service\Gpt\Request;

class GptSummarizeRequest extends GptRequest
{
    public $mainPromptTemplate;
    public $chunkSummarizePromptTemplate;
    public $summariesSummarizePromptTemplate;

    public $messages = [];

    public function __construct()
    {
        $this->mainPromptTemplate = $this->defaultMainPromptTemplate();
        $this->chunkSummarizePromptTemplate = $this->defaultChunkSummarizePromptTemplate();
        $this->summariesSummarizePromptTemplate = $this->defaultSummariesSummarizePromptTemplate();
    }

    /**
     * @param string $mainPromptTemplate
     * @return GptSummarizeRequest
     */
    public function setMainPromptTemplate(string $mainPromptTemplate) : self
    {
        $this->mainPromptTemplate = $mainPromptTemplate;

        return $this;
    }

    /**
     * @param $chunkSummarizePromptTemplate
     * @return GptSummarizeRequest
     */
    public function setChunkSummarizePromptTemplate(string $chunkSummarizePromptTemplate) : self
    {
        $this->chunkSummarizePromptTemplate = $chunkSummarizePromptTemplate;

        return $this;
    }

    /**
     * @param string $chunkSummarizePromptTemplate
     * @return GptSummarizeRequest
     */
    public function setSummariesSummarizePromptTemplate(string $chunkSummarizePromptTemplate) : self
    {
        $this->chunkSummarizePromptTemplate = $chunkSummarizePromptTemplate;

        return $this;
    }

    /**
     * @param array $messages
     * @return GptSummarizeRequest
     */
    public function setMessages(array $messages) : self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @param string $message
     * @return void
     */
    public function addMessage(string $message) : void
    {
        $this->messages[] = $message;
    }

    public function unsetLastMessage() : void
    {
        array_pop($this->messages);
    }

    /**
     * @return GptSummarizeRequest
     */
    public function prepareMainPrompt() : self
    {
        $this->userMessage = $this->bindVariables($this->mainPromptTemplate, [
            'messages' => implode(PHP_EOL.PHP_EOL, $this->messages)
        ]);

        return $this;
    }

    /**
     * @param string $chunk
     * @return GptSummarizeRequest
     */
    public function prepareChunkSummarizePrompt(string $chunk) : self
    {
        $prompt = '';
        $prompt .= $this->bindVariables($this->chunkSummarizePromptTemplate, [
            'message' => $chunk
        ]);

        $this->userMessage = $prompt;

        return $this;
    }

    /* Default templates */

    /**
     * Main prompt template
     */
    public function defaultMainPromptTemplate() : string
    {
        return  'Выдели основные моменты из сообщений. Результат отдай с заголовком "Краткое изложение" по каждому сообщению в отдельности.'.PHP_EOL.
                '[messages]';
    }

    /**
     * Chunk summarize prompt template
     */
    public function defaultChunkSummarizePromptTemplate() : string
    {
        return  'Выдели основные моменты сообщения. Результат отдай с заголовком "Краткое изложение".'.PHP_EOL.
                'Текст сообщения:'.PHP_EOL.
                '[message]'.PHP_EOL;
    }

    /**
     * Summaries summarize prompt template
     */
    public function defaultSummariesSummarizePromptTemplate() : string
    {
        return  'Тебе будут предоставлены краткие содержания переписки несколькими частями. Выдели основные моменты из всех частей и создай одно общее краткое изложение переписки. Результат отдай с заголовком "Краткое изложение".'.PHP_EOL.
                '[messages]';
    }

    /*public function buildOptions() : array
    {
        return [
            'api_key' => $this->getApiKey(),
            'model' => $this->model,
            'prompt' => $this->userMessage,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'frequency_penalty' => $this->frequencyPenalty,
            'presence_penalty' => $this->presencePenalty
        ];
    }*/
}