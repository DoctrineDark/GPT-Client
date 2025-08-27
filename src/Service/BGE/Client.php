<?php


namespace App\Service\BGE;


class Client
{
    private $originUrl;
    private $model = 'BAAI/bge-m3';
    private $timeout = 0;
    private $headers = [];
    private $contentTypes;
    private $curlInfo = [];

    public function __construct(string $originUrl)
    {
        $this->originUrl = $originUrl;

        $this->contentTypes = [
            "application/json" => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
            "application/x-www-form-urlencoded" => "Content-Type: application/x-www-form-urlencoded",
        ];

        //$this->headers = [$this->contentTypes["application/json"]];
    }

    public function embeddingModels(): array
    {
        return [
            'BAAI/bge-m3'
        ];
    }

    /**
     * @param array $options
     * @return bool|string
     * @throws \Exception
     */
    public function embeddings(array $options = [])
    {
        $options['model'] = $options['model'] ?? $this->model;

        $url = Url::embedding($this->originUrl);
        $response = $this->sendRequest($url, $options);

        return $response;
    }

    /**
     * @param Url $url
     * @param null $opts
     * @return bool|string
     * @throws \Exception
     */
    private function sendRequest(Url $url, $opts = null)
    {
        $post_fields = json_encode($opts);

        $headers = array_merge(
            //['Authorization: Bearer '.$this->apiKey],
            $this->headers
        );

        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
            $headers = array_merge($headers, [$this->contentTypes["multipart/form-data"]]);
            $post_fields = $opts;
        } else {
            $headers = array_merge($headers, [$this->contentTypes["application/json"]]);
        }

        $curl_info = [
            CURLOPT_URL => $url->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($url->method),
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
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
            throw new \Exception(curl_error($curl));
        }

        curl_close($curl);

        return $response;
    }
}