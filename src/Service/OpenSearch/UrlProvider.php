<?php


namespace App\Service\OpenSearch;


class UrlProvider
{
    private $originUrl;

    public function __construct(string $originUrl)
    {
        $this->originUrl = $originUrl;
    }

    /**
     * @param string $indexName
     * @param array $parameters
     * @return Url
     */
    public function createIndex(string $indexName, array $parameters = []): Url
    {
        $url = $this->originUrl . '' . $indexName;
        $method = 'put';
        $parameters = json_encode($parameters);
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }

    /**
     * @param string $indexName
     * @return Url
     */
    public function deleteIndex(string $indexName): Url
    {
        $url = $this->originUrl . '' . $indexName;
        $method = 'delete';
        $parameters = [];
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }

    /**
     * @param string $indexName
     * @return Url
     */
    public function getIndex(string $indexName): Url
    {
        $url = $this->originUrl . '' . $indexName;
        $method = 'get';
        $parameters = [];
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }

    /**
     * @param string $indexName
     * @param array $parameters
     * @return Url
     */
    public function insertVector(string $indexName, array $parameters)
    {
        $url = $this->originUrl . '' . $indexName . '/' . '_doc';
        $method = 'post';
        $parameters = json_encode($parameters);
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }

    /**
     * @param array $parameters
     * @return Url
     */
    public function bulkInsertVectors(array $parameters)
    {
        $url = $this->originUrl . '' . '_bulk';
        $method = 'post';
        $parameters = json_encode($parameters);
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }

    /**
     * @param string $indexName
     * @param array $parameters
     * @return Url
     */
    public function search(string $indexName, array $parameters)
    {
        $url = $this->originUrl . '' . $indexName . '/' . '_search';
        $method = 'post';
        $parameters = json_encode($parameters);
        $headers = [
            'Content-Type: application/json'
        ];

        return new Url($url, $method, $parameters, $headers);
    }
}