<?php


namespace App\Service\YandexGpt;


use Exception;

class Client
{
    private $apiKey;

    private $headers;
    private $contentTypes;
    private $timeout = 0;
    private $streamFunction;
    private $proxy = '';
    private $proxyAuth = '';
    private $curlInfo = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;

        $this->contentTypes = [
            'application/json' => 'Content-Type: application/json',
            'multipart/form-data' => 'Content-Type: multipart/form-data',
        ];

        $this->headers = [
            $this->contentTypes['application/json'],
            //'Authorization: Api-Key $apiKey',
        ];
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
     * @return array
     */
    public function models()
    {
        return [
            'yandexgpt-lite',
            'yandexgpt-lite/latest',
            'yandexgpt',
            'yandexgpt/latest'
        ];
    }

    /**
     * @param array $options
     * @param null $stream
     * @return string
     * @throws Exception
     */
    public function completion(array $options, $stream=null): string
    {
        if(
            array_key_exists('completionOptions', $options) &&
            array_key_exists('stream', $options['completionOptions']) &&
            !empty($options['completionOptions']['stream'])
        ) {
            if ($stream === null) {
                throw new Exception('Missing stream function.');
            }

            $this->streamFunction = $stream;
        }

        $url = Url::completion();

        return $this->sendRequest($url, 'POST', $options);
    }

    /**
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function tokenize(array $options): string
    {
        $url = Url::tokenize();

        return $this->sendRequest($url, 'POST', $options);
    }

    /**
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function tokenizeCompletion(array $options): string
    {
        $url = Url::tokenizeCompletion();

        return $this->sendRequest($url, 'POST', $options);
    }

    /**/

    /**
     * @param int $timeout
     * @return Client
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;

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

    /**
     * @param string $url
     * @param string $method
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    private function sendRequest(string $url, string $method, array $options = [])
    {
        $post_fields = json_encode($options);

        $headers = array_merge($this->headers, [
            'Authorization: Api-Key '.$this->apiKey,
            $this->contentTypes['application/json']
        ]);

        $curl_info = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (!empty($options)) {
            $curl_info[CURLOPT_POSTFIELDS] = $post_fields;
        }

        if (!empty($this->proxy)) {
            $curl_info[CURLOPT_PROXY] = $this->proxy;
        }

        if(
            array_key_exists('completionOptions', $options) &&
            array_key_exists('stream', $options['completionOptions']) &&
            !empty($options['completionOptions']['stream'])
        ) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->streamFunction;
        }

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info = curl_getinfo($curl);
        $this->curlInfo = $info;

        if (!$response) {
            throw new Exception(curl_error($curl));
        }

        curl_close($curl);

        return $response;
    }
}