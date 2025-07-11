<?php


namespace App\Service\Cloudflare\WorkersAI;


use App\Service\Gpt\Exception\GptServiceException;
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
     * @return array
     */
    public function getTextGenerationModels(): array
    {
        return [
            '@cf/qwen/qwen1.5-0.5b-chat',
            '@cf/huggingface/distilbert-sst-2-int8',
            '@cf/google/gemma-2b-it-lora',
            '@hf/nexusflow/starling-lm-7b-beta',
            '@cf/meta/llama-3-8b-instruct',
            '@cf/meta/llama-3.2-3b-instruct',
            '@hf/thebloke/llamaguard-7b-awq',
            '@hf/thebloke/neural-chat-7b-v3-1-awq',
            '@cf/meta/llama-guard-3-8b',
            '@cf/meta/llama-2-7b-chat-fp16',
            '@cf/mistral/mistral-7b-instruct-v0.1',
            '@cf/myshell-ai/melotts',
            '@cf/mistral/mistral-7b-instruct-v0.2-lora',
            '@cf/openai/whisper',
            '@cf/tinyllama/tinyllama-1.1b-chat-v1.0',
            '@hf/mistral/mistral-7b-instruct-v0.2',
            '@cf/fblgit/una-cybertron-7b-v2-bf16',
            '@cf/llava-hf/llava-1.5-7b-hf',
            '@cf/deepseek-ai/deepseek-r1-distill-qwen-32b',
            '@cf/runwayml/stable-diffusion-v1-5-inpainting',
            '@cf/black-forest-labs/flux-1-schnell',
            '@cf/thebloke/discolm-german-7b-v1-awq',
            '@cf/meta/llama-2-7b-chat-int8',
            '@cf/meta/llama-3.1-8b-instruct-fp8',
            '@hf/thebloke/mistral-7b-instruct-v0.1-awq',
            '@cf/qwen/qwen1.5-7b-chat-awq',
            '@cf/meta/llama-3.2-1b-instruct',
            '@hf/thebloke/llama-2-13b-chat-awq',
            '@cf/microsoft/resnet-50',
            '@cf/bytedance/stable-diffusion-xl-lightning',
            '@hf/thebloke/deepseek-coder-6.7b-base-awq',
            '@cf/meta-llama/llama-2-7b-chat-hf-lora',
            '@cf/meta/llama-3.3-70b-instruct-fp8-fast',
            '@cf/lykon/dreamshaper-8-lcm',
            '@cf/stabilityai/stable-diffusion-xl-base-1.0',
            '@hf/thebloke/openhermes-2.5-mistral-7b-awq',
            '@cf/meta/m2m100-1.2b',
            '@hf/thebloke/deepseek-coder-6.7b-instruct-awq',
            '@cf/qwen/qwen2.5-coder-32b-instruct',
            '@cf/deepseek-ai/deepseek-math-7b-instruct',
            '@cf/tiiuae/falcon-7b-instruct',
            '@hf/nousresearch/hermes-2-pro-mistral-7b',
            '@cf/meta/llama-3.1-8b-instruct-awq',
            '@cf/unum/uform-gen2-qwen-500m',
            '@hf/thebloke/zephyr-7b-beta-awq',
            '@cf/google/gemma-7b-it-lora',
            '@cf/qwen/qwen1.5-1.8b-chat',
            '@cf/mistralai/mistral-small-3.1-24b-instruct',
            '@cf/meta/llama-3-8b-instruct-awq',
            '@cf/meta/llama-3.2-11b-vision-instruct',
            '@cf/openai/whisper-tiny-en',
            '@cf/openai/whisper-large-v3-turbo',
            '@cf/defog/sqlcoder-7b-2',
            '@cf/microsoft/phi-2',
            '@hf/meta-llama/meta-llama-3-8b-instruct',
            '@cf/facebook/bart-large-cnn',
            '@cf/runwayml/stable-diffusion-v1-5-img2img',
            '@hf/google/gemma-7b-it',
            '@cf/qwen/qwen1.5-14b-chat-awq',
            '@cf/openchat/openchat-3.5-0106',
            '@cf/meta/llama-4-scout-17b-16e-instruct',
            '@cf/google/gemma-3-12b-it',
            '@cf/qwen/qwq-32b',

            //'@cf/baai/bge-small-en-v1.5',
            //'@cf/baai/bge-base-en-v1.5',
            //'@cf/baai/bge-large-en-v1.5',
            //'@cf/baai/bge-m3',
            //'@cf/baai/bge-reranker-base',
        ];
    }

    /**
     * @return array
     */
    public function getTextEmbeddingsModels(): array
    {
        return [
            '@cf/baai/bge-small-en-v1.5',
            '@cf/baai/bge-base-en-v1.5',
            '@cf/baai/bge-large-en-v1.5',
            '@cf/baai/bge-m3',
            //'@cf/baai/bge-reranker-base'
        ];
    }

    /**
     * @param string $model
     * @return int
     * @throws GptServiceException
     */
    public function getEmbeddingsDimension(string $model): int
    {
        switch ($model) {
            case '@cf/baai/bge-small-en-v1.5':
                return 384;
            case '@cf/baai/bge-base-en-v1.5':
                return 768;
            case '@cf/baai/bge-large-en-v1.5':
                return 1024;
            case '@cf/baai/bge-m3':
                return 1024;
            /*case '@cf/baai/bge-reranker-base':
                return 1024;*/
        }

        throw new GptServiceException('Invalid model');
    }

    /**
     * @param string $model
     * @param array $options
     * @return bool|string
     * @throws Exception
     */
    public function runModel(string $model, array $options)
    {
        $url = Url::runModel($this-> accountId, $model);
        $response = $this->sendRequest($url, $options);

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

        curl_close($curl);

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