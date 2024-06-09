<?php

namespace App\Service\Gpt\Request;

class GptQuestionRequest extends GptRequest
{
    public $entryTemplate;

    public $clientMessage;
    public $clientMessageTemplate;
    public $fullClientMessage;

    public $lists;
    public $listsMessageTemplate;
    public $listsMessages = [];

    public $checkboxes = [];
    public $checkboxesMessageTemplate;
    public $checkboxesMessages = [];

    public $userMessages;

    public function __construct()
    {
        $this->entryTemplate = $this->defaultEntryTemplate();
        $this->listsMessageTemplate = $this->defaultListsMessageTemplate();
        $this->checkboxesMessageTemplate = $this->defaultCheckboxesMessageTemplate();
    }

    /**
     * @return $this
     */
    public function preparePrompt()
    {
        $prompt = '';

        if(!$this->clientMessageTemplate) {
            $this->clientMessageTemplate = $this->defaultClientMessageTemplate();
        }

        if($this->lists || $this->checkboxes) {
            $prompt .= $this->entryTemplate;

            foreach($this->lists as $list) {
                $this->listsMessages[] = $this->prepareListsMessage([$list], $this->listsMessageTemplate);
            }
            $prompt .= implode(PHP_EOL, $this->listsMessages);

            foreach($this->checkboxes as $checkbox) {
                $this->checkboxesMessages[] = $this->prepareCheckboxesMessage([$checkbox], $this->checkboxesMessageTemplate);
            }
            $prompt .= implode(PHP_EOL, $this->checkboxesMessages);
        }

        $this->fullClientMessage = $this->prepareClientMessageTemplate($this->clientMessage, $this->clientMessageTemplate);
        $prompt .= $this->fullClientMessage;

        $this->userMessage = $prompt;

        return $this;
    }

    /**
     * @param string $template
     * @return GptQuestionRequest
     */
    public function setEntryTemplate(string $template) : self
    {
        $this->entryTemplate = $template;

        return $this;
    }

    /**
     * @param string $message
     * @return GptQuestionRequest
     */
    public function setClientMessage(string $message) : self
    {
        $this->clientMessage = $message;

        return $this;
    }

    /**
     * @param array $lists
     * @param array $values
     * @return GptQuestionRequest
     */
    public function setLists(array $lists, array $values) : self
    {
        $this->lists = $this->prepareLists($lists, $values);

        return $this;
    }

    /**
     * @param string $template
     * @return GptQuestionRequest
     */
    public function setListsMessageTemplate(string $template) : self
    {
        $this->listsMessageTemplate = $template;

        return $this;
    }

    /**
     * @param array $checkboxes
     * @return GptQuestionRequest
     */
    public function setCheckboxes(array $checkboxes) : self
    {
        $this->checkboxes = $checkboxes;

        return $this;
    }

    /**
     * @param string $template
     * @return GptQuestionRequest
     */
    public function setCheckboxesMessageTemplate(string $template) : self
    {
        $this->checkboxesMessageTemplate = $template;

        return $this;
    }

    /**
     * @param string|null $template
     * @return GptQuestionRequest
     */
    public function setClientMessageTemplate(string $template) : self
    {
        $this->clientMessageTemplate = $template;

        return $this;
    }

    /**
     * @param array $lists
     * @param array $values
     * @return array
     */
    public function prepareLists(array $lists, array $values) : array
    {
        $res = [];

        foreach($lists as $key => $name) {
            $res[] = [$name => isset($values[$key]) ? $values[$key] : []];
        }

        return $res;
    }

    /**
     * @param array $lists
     * @param string|null $template
     * @return string|null
     *
     * Template variables: [list], [list_values]
     */
    public function prepareListsMessage(array $lists, string $template) : string
    {
        $message = '';

        foreach ($lists as $list) {
            foreach ($list as $name => $values) {
                $message .= $this->bindVariables($template, [
                    'list' => $name,
                    'list_values' => implode(PHP_EOL, array_map(function($k, $v) {
                        return ($k+1).') '.$v;
                    }, array_keys($values), $values))
                ]);
                $message .= PHP_EOL;
            }
        }

        return $message;
    }

    /**
     * @param array $checkboxes
     * @param string $template
     * @return string
     *
     * Template variables: [checkbox]
     */
    public function prepareCheckboxesMessage(array $checkboxes, string $template) : string
    {
        $message = '';

        foreach ($checkboxes as $checkbox) {
            $message .= $this->bindVariables($template, [
                'checkbox' => $checkbox
            ]);
            $message .= PHP_EOL;
        }

        return $message;
    }

    /**
     * @param string $message
     * @param string $template
     * @return string
     */
    public function prepareClientMessageTemplate(?string $message, ?string $template) : string
    {
        if($message && $template) {
            return $this->bindVariables($template, ['user_message' => $message]);
        }

        return $template;
    }

    /**
     * @return string
     */
    public function defaultEntryTemplate() : string
    {
        return 'Нужна твоя помощь для анализа текста сообщений клиентов.'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [list], [list_values]
     */
    public function defaultListsMessageTemplate() : string
    {
        return 'По тексту сообщения клиента определи одно самое подходящее значение параметра "[list]" и коротко сообщи его.'.PHP_EOL.
            'Варианты параметра:'.PHP_EOL.
            '[list_values].'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [checkbox]
     */
    public function defaultCheckboxesMessageTemplate() : string
    {
        return 'По тексту сообщения клиента определи одно самое подходящее значение параметра "[checkbox]" и коротко сообщи его.'.PHP_EOL.
            'Варианты параметра:'.PHP_EOL.
            '1) Да'.PHP_EOL.
            '2) Нет.'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [user_message]
     */
    public function defaultClientMessageTemplate() : string
    {
        return 'Текст сообщения клиента для анализа:'.PHP_EOL.
            '"[user_message]".'.PHP_EOL;
    }
}