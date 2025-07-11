<?php


namespace App\Service\Cloudflare\Vectorize;


use Exception;

class Client
{
    private $accountId;
    private $apiKey;

    private $headers;
    private $timeout = 0;
    private $proxy = "";
    private $proxyAuth = "";
    private $curlInfo = [];

    public function __construct(string $accountId, string $apiKey)
    {
        $this->accountId = $accountId;
        $this->apiKey = $apiKey;
    }

    /**
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function createIndex(array $options)
    {
        $url = Url::createIndex($this->accountId);
        $response = $this->sendRequest($url, $options);

        return $response;
    }

    /**
     * @param string $indexName
     * @return bool|string
     * @throws Exception
     */
    public function deleteIndex(string $indexName)
    {
        $url = Url::deleteIndex($this->accountId, $indexName);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function deleteIndexById(string $indexName, array $options)
    {
        $url = Url::deleteIndexById($this->accountId, $indexName);
        $response = $this->sendRequest($url, $options);

        return $response;
    }

    /**
     * @param string $indexName
     * @return bool|string
     * @throws Exception
     */
    public function getIndex(string $indexName)
    {
        $url = Url::getIndex($this->accountId, $indexName);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function getVectorsById(string $indexName, array $options)
    {
        $url = Url::getVectorsById($this->accountId, $indexName);
        $response = $this->sendRequest($url, $options);

        return $response;
    }

    /**
     * @param string $indexName
     * @return bool|string
     * @throws Exception
     */
    public function getIndexInfo(string $indexName)
    {
        $url = Url::getIndexInfo($this->accountId, $indexName);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function insertVectors(string $indexName, array $options)
    {
        $url = Url::insertVectors($this->accountId, $indexName);
        $response = $this->sendRequest($url, Ndjson::encode($options));

        return $response;
    }

    /**
     * @return bool|string
     * @throws Exception
     */
    public function listIndexes()
    {
        $url = Url::listIndexes($this->accountId);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function queryVectors(string $indexName, array $options)
    {
        $url = Url::queryVectors($this->accountId, $indexName);
        $response = $this->sendRequest($url, $options);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function upsertVectors(string $indexName, array $options)
    {
        $url = Url::upsertVectors($this->accountId, $indexName);
        $response = $this->sendRequest($url, Ndjson::encode($options));

        return $response;
    }

    /**
     * @param Url $url
     * @param array $opts
     * @return bool|string
     * @throws Exception
     */
    private function sendRequest(Url $url, $opts = null)
    {
        // Headers
        $this->headers = array_merge(
            ['Authorization: Bearer ' . $this->apiKey],
            $url->headers
        );

        $curl_info = [
            CURLOPT_URL => $url->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($url->method),
            CURLOPT_HTTPHEADER => $this->headers,
        ];

        if (!empty($opts)) { $curl_info[CURLOPT_POSTFIELDS] = is_array($opts) ? json_encode($opts) : $opts; }
        if (!empty($this->proxy)) { $curl_info[CURLOPT_PROXY] = $this->proxy; }
        if (!empty($this->proxyAuth)) { $curl_info[CURLOPT_PROXYUSERPWD] = $this->proxyAuth; }

        $curl = curl_init();
        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info = curl_getinfo($curl);
        $this->curlInfo = $info;

        if (!$response) {
            throw new Exception(curl_error($curl));
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getCURLInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @param string $accountId
     * @return $this
     */
    public function setAccountId(string $accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @param string $apiKey
     * @return $this
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $proxy
     * @return Client
     */
    public function setProxy(string $proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @param string $credentials
     * @return Client
     */
    public function setProxyAuth(string $credentials)
    {
        $this->proxyAuth = $credentials;

        return $this;
    }
}