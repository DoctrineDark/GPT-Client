<?php

namespace App\Service\Gpt;

class GptResponse
{
    public $response;

    public function __construct($response)
    {
        $this->response = $response;
    }
}