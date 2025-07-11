<?php


namespace App\Service\Cloudflare\Vectorize;


class Url
{
    const ORIGIN = 'https://api.cloudflare.com/';
    const CLIENT = 'client/v4';

    public $url;
    public $method;
    public $headers;

    public function __construct(string $url, string $method, array $headers = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->headers = $headers;
    }

    /**
     * Creates and returns a new Vectorize Index.
     * Body Parameters:
     *  {
     *      "config": {
     *          "dimensions": 768,
     *          "metric": "cosine"
     *      },
     *      "name": "example-index",
     *      "description": "This is my example index."
     *  }
     *
     * @param string $accountId
     * @return self
     */
    public static function createIndex(string $accountId): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes";
        $method = 'post';
        $headers = [
            'Content-Type: application/json',
        ];

        return new self($url, $method, $headers);
    }

    /**
     * Deletes the specified Vectorize Index.
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function deleteIndex(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}";
        $method = 'delete';

        return new self($url, $method);
    }

    /**
     * Delete a set of vectors from an index by their vector identifiers.
     * Body Parameters:
     *  {
     *      "ids": [
     *          "5121db81354a40c6aedc3fe1ace51c59",
     *          "f90eb49c2107486abdfd78c67e853430"
     *      ]
     *  }
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function deleteIndexById(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/delete_by_ids";
        $method = 'post';
        $headers = [
            'Content-Type: application/json',
        ];

        return new self($url, $method, $headers);
    }

    /**
     * Returns the specified Vectorize Index.
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function getIndex(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}";
        $method = 'get';

        return new self($url, $method);
    }

    /**
     * Get a set of vectors from an index by their vector identifiers.
     * Body parameters:
     *
     *  {
     *      "ids": [
     *          "5121db81354a40c6aedc3fe1ace51c59",
     *          "f90eb49c2107486abdfd78c67e853430"
     *      ]
     *  }
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function getVectorsById(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/get_by_ids";
        $method = 'post';
        $headers = [
            'Content-Type: application/json',
        ];

        return new self($url, $method, $headers);
    }

    /**
     * Get information about a vectorize index.
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function getIndexInfo(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/info";
        $method = 'get';

        return new self($url, $method);
    }

    /**
     * Inserts vectors into the specified index and returns a mutation id corresponding to the vectors enqueued for insertion.
     * Body Parameters: vectors.ndjson
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function insertVectors(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/insert";
        $method = 'post';
        $headers = [
            'Content-Type: application/x-ndjson',
        ];

        return new self($url, $method, $headers);
    }

    /**
     * Returns a list of Vectorize Indexes
     *
     * @param string $accountId
     * @return self
     */
    public static function listIndexes(string $accountId): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes";
        $method = 'get';

        return new self($url, $method);
    }

    /**
     * Finds vectors closest to a given vector in an index.
     * Body Parameters:
     *  {
     *      "vector": [
     *          0.5,
     *          0.5,
     *          0.5
     *      ],
     *      "filter": {
     *          "has_viewed": {
     *              "$ne": true
     *          },
     *          "streaming_platform": "netflix"
     *      },
     *      "topK": 5
     *  }
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function queryVectors(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/query";
        $method = 'post';
        $headers = [
            'Content-Type: application/json',
        ];

        return new self($url, $method, $headers);
    }

    /**
     * Upserts vectors into the specified index, creating them if they do not exist and returns a mutation id corresponding to the vectors enqueued for upsertion.
     * Body Parameters: vectors.ndjson
     *
     * @param string $accountId
     * @param string $indexName
     * @return self
     */
    public static function upsertVectors(string $accountId, string $indexName): self
    {
        $url = self::ORIGIN . self::CLIENT . "/accounts/{$accountId}/vectorize/v2/indexes/{$indexName}/upsert";
        $method = 'post';
        $headers = [
            'Content-Type: application/x-ndjson',
        ];

        return new self($url, $method, $headers);
    }
}