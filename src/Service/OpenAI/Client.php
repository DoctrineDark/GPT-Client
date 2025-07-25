<?php

namespace App\Service\OpenAI;

use Exception;

class Client
{
    private $apiKey;

    private $engine = "davinci";
    private $model = "text-davinci-002";
    private $chatModel = "gpt-3.5-turbo";
    private $headers;
    private $contentTypes;
    private $timeout = 0;
    private $stream_method;
    private $customUrl = "";
    private $proxy = "";
    private $proxyAuth = "";
    private $curlInfo = [];

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;

        $this->contentTypes = [
            "application/json" => "Content-Type: application/json",
            "multipart/form-data" => "Content-Type: multipart/form-data",
        ];

        $this->headers = [
            $this->contentTypes["application/json"],
            //"Authorization: Bearer $OPENAI_API_KEY",
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
     * Remove this method from your code before deploying
     */
    public function getCURLInfo()
    {
        return $this->curlInfo;
    }

    /**
     * @return array
     */
    public function models(): array
    {
        return [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini',
        ];
    }

    /**
     * @return bool|string
     * @throws Exception
     */
    public function listModels()
    {
        $url = Url::fineTuneModel();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $model
     * @return bool|string
     * @throws Exception
     */
    public function retrieveModel($model)
    {
        $model = "/$model";
        $url = Url::fineTuneModel() . $model;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function complete($opts)
    {
        $engine = $opts['engine'] ?? $this->engine;
        $url = Url::completionURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param        $opts
     * @param null $stream
     * @return bool|string
     * @throws Exception
     */
    public function completion($opts, $stream = null)
    {
        if (array_key_exists('stream', $opts) && $opts['stream']) {
            if ($stream == null) {
                throw new Exception(
                    'Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.'
                );
            }

            $this->stream_method = $stream;
        }

        $opts['model'] = $opts['model'] ?? $this->model;
        $url = Url::completionsURL();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function createEdit($opts)
    {
        $url = Url::editsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function image($opts)
    {
        $url = Url::imageUrl() . "/generations";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function imageEdit($opts)
    {
        $url = Url::imageUrl() . "/edits";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function createImageVariation($opts)
    {
        $url = Url::imageUrl() . "/variations";
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function search($opts)
    {
        $engine = $opts['engine'] ?? $this->engine;
        $url = Url::searchURL($engine);
        unset($opts['engine']);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function answer($opts)
    {
        $url = Url::answersUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function classification($opts)
    {
        $url = Url::classificationsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function moderation($opts)
    {
        $url = Url::moderationUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param        $opts
     * @param null $stream
     * @return bool|string
     * @throws Exception
     */
    public function chat($opts, $stream = null)
    {
        if ($stream != null && array_key_exists('stream', $opts)) {
            if (!$opts['stream']) {
                throw new Exception(
                    'Please provide a stream function. Check https://github.com/orhanerday/open-ai#stream-example for an example.'
                );
            }

            $this->stream_method = $stream;
        }

        $opts['model'] = $opts['model'] ?? $this->chatModel;
        $url = Url::chatUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function transcribe($opts)
    {
        $url = Url::transcriptionsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function translate($opts)
    {
        $url = Url::translationsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function uploadFile($opts)
    {
        $url = Url::filesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     * @throws Exception
     */
    public function listFiles()
    {
        $url = Url::filesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     * @throws Exception
     */
    public function retrieveFile($file_id)
    {
        $file_id = "/$file_id";
        $url = Url::filesUrl() . $file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     * @throws Exception
     */
    public function retrieveFileContent($file_id)
    {
        $file_id = "/$file_id/content";
        $url = Url::filesUrl() . $file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $file_id
     * @return bool|string
     * @throws Exception
     */
    public function deleteFile($file_id)
    {
        $file_id = "/$file_id";
        $url = Url::filesUrl() . $file_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function createFineTune($opts)
    {
        $url = Url::fineTuneUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @return bool|string
     * @throws Exception
     */
    public function listFineTunes()
    {
        $url = Url::fineTuneUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     * @throws Exception
     */
    public function retrieveFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id";
        $url = Url::fineTuneUrl() . $fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     * @throws Exception
     */
    public function cancelFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id/cancel";
        $url = Url::fineTuneUrl() . $fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     * @throws Exception
     */
    public function listFineTuneEvents($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id/events";
        $url = Url::fineTuneUrl() . $fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $fine_tune_id
     * @return bool|string
     * @throws Exception
     */
    public function deleteFineTune($fine_tune_id)
    {
        $fine_tune_id = "/$fine_tune_id";
        $url = Url::fineTuneModel() . $fine_tune_id;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function engines()
    {
        $url = Url::enginesUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $engine
     * @return bool|string
     * @throws Exception
     * @deprecated
     */
    public function engine($engine)
    {
        $url = Url::engineUrl($engine);
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param $opts
     * @return bool|string
     * @throws Exception
     */
    public function embeddings($opts)
    {
        $url = Url::embeddings();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $opts);
    }

    /**
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function createAssistant($data)
    {
        $data['model'] = $data['model'] ?? $this->chatModel;
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $assistantId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveAssistant($assistantId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $assistantId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function modifyAssistant($assistantId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $assistantId
     * @return bool|string
     * @throws Exception
     */
    public function deleteAssistant($assistantId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listAssistants($query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl();
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $assistantId
     * @param string $fileId
     * @return bool|string
     * @throws Exception
     */
    public function createAssistantFile($assistantId, $fileId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId . '/files';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', ['file_id' => $fileId]);
    }

    /**
     * @param string $assistantId
     * @param string $fileId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveAssistantFile($assistantId, $fileId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId . '/files/' . $fileId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $assistantId
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listAssistantFiles($assistantId, $query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId . '/files';
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $assistantId
     * @param string $fileId
     * @return bool|string
     * @throws Exception
     */
    public function deleteAssistantFile($assistantId, $fileId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::assistantsUrl() . '/' . $assistantId . '/files/' . $fileId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function createThread($data = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl();
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveThread($threadId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function modifyThread($threadId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @return bool|string
     * @throws Exception
     */
    public function deleteThread($threadId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'DELETE');
    }

    /**
     * @param string $threadId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function createThreadMessage($threadId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @param string $messageId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveThreadMessage($threadId, $messageId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages/' . $messageId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $messageId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function modifyThreadMessage($threadId, $messageId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages/' . $messageId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listThreadMessages($threadId, $query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages';
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $messageId
     * @param string $fileId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveMessageFile($threadId, $messageId, $fileId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages/' . $messageId . '/files/' . $fileId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $messageId
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listMessageFiles($threadId, $messageId, $query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/messages/' . $messageId . '/files';
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function createRun($threadId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveRun($threadId, $runId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function modifyRun($threadId, $runId, $data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listRuns($threadId, $query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs';
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @param array $outputs
     * @return bool|string
     * @throws Exception
     */
    public function submitToolOutputs($threadId, $runId, $outputs)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId . '/submit_tool_outputs';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $outputs);
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @return bool|string
     * @throws Exception
     */
    public function cancelRun($threadId, $runId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId . '/cancel';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST');
    }

    /**
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function createThreadAndRun($data)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/runs';
        $this->baseUrl($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @param string $stepId
     * @return bool|string
     * @throws Exception
     */
    public function retrieveRunStep($threadId, $runId, $stepId)
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId . '/steps/' . $stepId;
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param string $threadId
     * @param string $runId
     * @param array $query
     * @return bool|string
     * @throws Exception
     */
    public function listRunSteps($threadId, $runId, $query = [])
    {
        $this->headers[] = 'OpenAI-Beta: assistants=v2';
        $url = Url::threadsUrl() . '/' . $threadId . '/runs/' . $runId . '/steps';
        if (count($query) > 0) {
            $url .= '?' . http_build_query($query);
        }
        $this->baseUrl($url);

        return $this->sendRequest($url, 'GET');
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout)
    {
        $this->timeout = $timeout;
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
     * @param string $customUrl
     * @deprecated
     */

    /**
     * @param string $customUrl
     * @return void
     */
    public function setCustomURL(string $customUrl)
    {
        if ($customUrl != "") {
            $this->customUrl = $customUrl;
        }
    }

    /**
     * @param string $customUrl
     * @return void
     */
    public function setBaseURL(string $customUrl)
    {
        if ($customUrl != '') {
            $this->customUrl = $customUrl;
        }
    }

    /**
     * @param array $header
     * @return void
     */
    public function setHeader(array $header)
    {
        if ($header) {
            foreach ($header as $key => $value) {
                $this->headers[$key] = $value;
            }
        }
    }

    /**
     * @param string $org
     */
    public function setORG(string $org)
    {
        if ($org != "") {
            $this->headers[] = "OpenAI-Organization: $org";
        }
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $opts
     * @return bool|string
     * @throws Exception
     */
    private function sendRequest(string $url, string $method, array $opts = [])
    {
        $post_fields = json_encode($opts);

        $headers = array_merge($this->headers, [
            'Authorization: Bearer '.$this->apiKey
        ]);

        if (array_key_exists('file', $opts) || array_key_exists('image', $opts)) {
            $headers = array_merge($headers, [$this->contentTypes["multipart/form-data"]]);
            $post_fields = $opts;
        } else {
            $headers = array_merge($headers, [$this->contentTypes["application/json"]]);
        }

        $curl_info = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }

        if (!empty($this->proxy)) {
            $curl_info[CURLOPT_PROXY] = $this->proxy;
        }

        if (!empty($this->proxyAuth)) {
            $curl_info[CURLOPT_PROXYUSERPWD] = $this->proxyAuth;
        }

        if (array_key_exists('stream', $opts) && $opts['stream']) {
            $curl_info[CURLOPT_WRITEFUNCTION] = $this->stream_method;
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

    /**
     * @param string $url
     */
    private function baseUrl(string &$url)
    {
        if ($this->customUrl != "") {
            $url = str_replace(Url::ORIGIN, $this->customUrl, $url);
        }
    }
}