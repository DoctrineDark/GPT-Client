<?php

namespace App\Service\Gpt\Contract;

use App\Service\Gpt\GptRequest;
use App\Service\Gpt\GptResponse;

interface Gpt
{
    /**
     * @param GptRequest $request
     * @return GptResponse
     */
    public function request(GptRequest $request) : GptResponse;

    /**
     * @param string $name
     * @return bool
     */
    public function supports(string $name) : bool;
}