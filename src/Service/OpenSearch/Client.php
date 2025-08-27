<?php


namespace App\Service\OpenSearch;


class Client
{
    private $originUrl;
    private $user;
    private $password;

    private $urlProvider;

    private $curlInfo = [];
    private $timeout = 0;

    public function __construct(string $originUrl, string $user, string $password)
    {
        $this->originUrl = $originUrl;
        $this->user = $user;
        $this->password = $password;

        $this->urlProvider = new UrlProvider($originUrl);
    }

    /**
     * @param string $indexName
     * @param array $options
     * @return bool|string
     * @throws \Exception
     */
    public function createIndex(string $indexName, array $options)
    {
        $url = $this->urlProvider->createIndex($indexName, $options);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @return bool|string
     * @throws \Exception
     */
    public function deleteIndex(string $indexName)
    {
        $url = $this->urlProvider->deleteIndex($indexName);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @return bool|string
     * @throws \Exception
     */
    public function getIndex(string $indexName)
    {
        $url = $this->urlProvider->getIndex($indexName);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $parameters
     * @return bool|string
     * @throws \Exception
     */
    public function insertVector(string $indexName, array $parameters)
    {
        $url = $this->urlProvider->insertVector($indexName, $parameters);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param string $indexName
     * @param array $parameters
     * @return bool|string
     * @throws \Exception
     */
    public function search(string $indexName, array $parameters)
    {
        $url = $this->urlProvider->search($indexName, $parameters);
        $response = $this->sendRequest($url);

        return $response;
    }

    /**
     * @param Url $url
     * @return bool|string
     * @throws \Exception
     */
    private function sendRequest(Url $url)
    {
        $curl_info = [
            CURLOPT_URL => $url->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($url->method),
            CURLOPT_POSTFIELDS => $url->parameters,
            CURLOPT_HTTPHEADER => $url->headers,
        ];

        if (empty($url->parameters)) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if ($this->user && $this->password) {
            $curl_info[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $curl_info[CURLOPT_USERPWD] = "$this->user:$this->password";
        }

        $curl = curl_init();
        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info = curl_getinfo($curl);
        $this->curlInfo = $info;

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Exception($error);
        }

        if (!$response) {
            curl_close($curl);
            throw new \Exception(curl_error($curl));
        }

        curl_close($curl);

        return $response;
    }
}