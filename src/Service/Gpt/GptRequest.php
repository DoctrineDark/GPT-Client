<?php

namespace App\Service\Gpt;

class GptRequest
{
    public $apiKey;

    public $entryTemplate;

    public $clientMessage;
    public $clientMessageTemplate;

    public $lists;
    public $listsMessageTemplate;

    public $checkboxes;
    public $checkboxesMessageTemplate;

    public $customMessageTemplate;

    public $systemMessage;
    public $userMessage;

    public $raw;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;

        $this->entryTemplate = $this->entryTemplate();
        $this->listsMessageTemplate = $this->listsMessageTemplate();
        $this->checkboxesMessageTemplate = $this->checkboxesMessageTemplate();
    }

    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setSystemMessage()
    {
        return $this;
    }

    public function setUserMessage()
    {
        $message = $this->entryTemplate;
        $message .= $this->prepareListsMessage($this->lists, $this->listsMessageTemplate);
        $message .= $this->prepareCheckboxesMessage($this->checkboxes, $this->checkboxesMessageTemplate);
        $message .= $this->prepareCustomMessageTemplate($this->clientMessage, $this->customMessageTemplate);

        dd($message);

        $this->userMessage = $message;

        return $this;
    }

    public function setRaw(?string $json) : self
    {
        $this->raw = $json;

        return $this;
    }

    /**
     * @param string $template
     * @return GptRequest
     */
    public function setEntryTemplate(string $template) : self
    {
        $this->entryTemplate = $template;

        return $this;
    }

    /**
     * @param string $message
     * @return GptRequest
     */
    public function setClientMessage(string $message) : self
    {
        $this->clientMessage = $message;

        return $this;
    }

    /**
     * @param array $lists
     * @param array $values
     * @return GptRequest
     */
    public function setLists(array $lists, array $values) : self
    {
        $this->lists = $this->prepareLists($lists, $values);

        return $this;
    }

    /**
     * @param string $template
     * @return GptRequest
     */
    public function setListsMessageTemplate(string $template) : self
    {
        $this->listsMessageTemplate = $template;

        return $this;
    }

    /**
     * @param array $checkboxes
     * @return GptRequest
     */
    public function setCheckboxes(array $checkboxes) : self
    {
        $this->checkboxes = $checkboxes;

        return $this;
    }

    /**
     * @param string $template
     * @return GptRequest
     */
    public function setCheckboxesMessageTemplate(string $template) : self
    {
        $this->checkboxesMessageTemplate = $template;

        return $this;
    }

    /**
     * @param string|null $template
     * @return GptRequest
     */
    public function setCustomMessageTemplate(?string $template) : self
    {
        $this->customMessageTemplate = $template;

        return $this;
    }

    /**
     * @param array $list
     * @param array $values
     * @return array
     */
    public function prepareLists(array $list, array $values) : array
    {
        $res = [];

        foreach($list as $key => $name) {
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
    public function prepareCustomMessageTemplate(?string $message, ?string $template) : ?string
    {
        if($message && $template) {
            return $this->bindVariables($template, ['user_message' => $message]);
        }

        return null;
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
     * @return string
     */
    public function entryTemplate() : string
    {
        return 'Нужна твоя помощь для анализа текста сообщений клиентов.'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [list], [list_values]
     */
    public function listsMessageTemplate() : string
    {
        return 'По тексту сообщения клиента определи одно самое подходящее значение параметра "[list]" и коротко сообщи его.'.PHP_EOL.
            'Варианты параметра:'.PHP_EOL.
            '[list_values]'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [checkbox]
     */
    public function checkboxesMessageTemplate() : string
    {
        return 'По тексту сообщения клиента определи одно самое подходящее значение параметра "[checkbox]" и коротко сообщи его.'.PHP_EOL.
            'Варианты параметра:'.PHP_EOL.
            '1) Да'.PHP_EOL.
            '2) Нет'.PHP_EOL;
    }

    /**
     * @return string
     * Template variables: [client_message]
     */
    public function clientMessageTemplate() : string
    {
        return 'Текст сообщения клиента для анализа:'.PHP_EOL.
            '"[client_message]"';
    }
}