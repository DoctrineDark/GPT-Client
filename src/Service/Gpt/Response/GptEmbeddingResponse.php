<?php

namespace App\Service\Gpt\Response;

class GptEmbeddingResponse
{
    /** @var string|null */
    public $model;

    /** @var array|null */
    public $embedding;

    /** @var integer|null */
    public $prompt_tokens;

    /** @var integer|null */
    public $total_tokens;
}