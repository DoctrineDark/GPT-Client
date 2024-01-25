<?php

namespace App\Service\Gpt;

use App\Service\Gpt\Contract\Gpt;
use App\Service\OpenAI\Client;

class OpenAIClient implements Gpt
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function request(GptRequest $request): GptResponse
    {
        if($request->raw) {

            $response = ['raw'];
            return new GptResponse($response);
        }

        /*$response = $this->client->chat([
            'api_key' => $request->apiKey
        ]);*/

        $response = $response = ['custom'];
        return new GptResponse($response);
    }

    public function supports(string $name) : bool
    {
        return 'openai' === $name;
    }
}