<?php


namespace App\Service\Gpt\Response;


class GptAssistantResponse
{
    /** @var string|null */
    public $id;

    /** @var string|null */
    public $assistant_id;

    /** @var string|null */
    public $thread_id;

    /** @var string|null */
    public $run_id;

    /** @var string|null */
    public $model;

    /** @var string|null */
    public $datetime;

    /** @var string|null */
    public $message;

    /** @var string|null */
    public $object;

    /** @var integer|null */
    public $prompt_tokens;

    /** @var integer|null */
    public $completion_tokens;

    /** @var integer|null */
    public $total_tokens;
}