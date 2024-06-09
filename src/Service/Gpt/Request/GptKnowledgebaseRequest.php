<?php

namespace App\Service\Gpt\Request;

class GptKnowledgebaseRequest extends GptRequest
{
    public $userMessageTemplate;
    public $question;
    public $content;

    public function __construct()
    {
        $this->userMessageTemplate = $this->getDefaultUserMessageTemplate();
    }

    /**
     * @param $userMessageTemplate
     * @return GptKnowledgebaseRequest
     */
    public function setUserMessageTemplate($userMessageTemplate) : self
    {
        $this->userMessageTemplate = $userMessageTemplate;

        return $this;
    }

    /**
     * @param mixed $question
     * @return GptKnowledgebaseRequest
     */
    public function setQuestion($question) : self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * @param mixed $content
     * @return GptKnowledgebaseRequest
     */
    public function setContent($content) : self
    {
        $this->content = $content;

        return $this;
    }

    public function preparePrompt() : self
    {
        $this->userMessage = $this->bindVariables($this->userMessageTemplate, [
            'content' => $this->content,
            'question' => $this->question
        ]);

        return $this;
    }

    /**
     * @return string
     * Template variables: [content, question]
     */
    public function getDefaultUserMessageTemplate() : string
    {
        return 'Используй приведенную ниже статью, чтобы ответить на следующий вопрос. Если ответ не найден, напиши: "Оператор скоро подключится".'.PHP_EOL.
                PHP_EOL.
                'Статья:'.PHP_EOL.
                '[content]'.PHP_EOL.
                PHP_EOL.
                'Вопрос:'.PHP_EOL.
                '[question]'.PHP_EOL;
    }
}