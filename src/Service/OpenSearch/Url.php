<?php


namespace App\Service\OpenSearch;


class Url
{
    public $url;
    public $method;
    public $parameters;
    public $headers;

    public function __construct(string $url, string $method, $parameters, array $headers = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->parameters = $parameters;
        $this->headers = $headers;
    }
}