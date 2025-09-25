<?php

namespace App\Service\VectorSearch;


use App\Service\Gpt\Response\GptEmbeddingResponse;
use Predis\Client;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\NumericField;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\Response\ServerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class RedisSearcher
{
    public const DELIMITER = ':';
    public const ROOT = 'embeddings';
    public const INDEX = 'idx:embeddings';

    private $client;
    private $denormalizer;

    public function __construct(Client $client, DenormalizerInterface $denormalizer)
    {
        $this->client = $client;
        $this->denormalizer = $denormalizer;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key) : bool
    {
        /**
         * FORCE DISABLED
         */
        return false;

        return boolval($this->client->exists($key));
    }

    /**
     * @param string $match
     * @param int|null $cursor
     * @param int|null $count
     * @param string|null $type
     * @return array
     */
    public function scan(string $match, int $cursor = 0, ?int $count = null, ?string $type = null): array
    {
        $options['MATCH'] = $match;

        if($count) {
            $options['COUNT'] = $count;
        }

        if($type) {
            $options['TYPE'] = $type;
        }

        return $this->client->scan($cursor, $options);
    }

    /**
     * @param string[]|string $keyOrKeys
     * @return bool
     */
    public function delete($keyOrKeys) : bool
    {
        return boolval($this->client->del($keyOrKeys));
    }

    /**
     * @param Embedding $embedding
     * @param string $key
     * @param string $path
     * @return string
     */
    public function setEmbedding(Embedding $embedding, string $key, string $path = '$')
    {
        $response = $this->client->jsonset($key, $path, json_encode($embedding));

        return $response;
    }

    /**
     * @param GptEmbeddingResponse $embeddingResponse
     * @param int $k
     * @param float $distanceLimit
     * @return array<SearchResponse>
     */
    public function search(GptEmbeddingResponse $embeddingResponse, int $k = 2, float $distanceLimit = 0.99): array
    {
        $this->createIndex($embeddingResponse->dimensions);

        $binaryVector = '';
        foreach ($embeddingResponse->embedding as $value) {
            $binaryVector .= pack('f', $value);
        }

        $titleSearchResult = $this->client->ftsearch(
            self::INDEX,
            "(*)=>[KNN $k @title_embedding \$vector AS distance]",
            (new SearchArguments())
                ->params(['vector', $binaryVector])
                ->sortBy('distance', 'ASC')
                ->addReturn(3, 'type', 'id', 'distance')
                ->dialect('2')
        );

        $contentSearchResult = $this->client->ftsearch(
            self::INDEX,
            "(*)=>[KNN $k @content_embedding \$vector AS distance]",
            (new SearchArguments())
                ->params(['vector', $binaryVector])
                ->sortBy('distance', 'ASC')
                ->addReturn(3, 'type', 'id', 'distance')
                ->dialect('2')
        );

        $result = $this->unifyResults([
            $this->parseRedisResult($titleSearchResult),
            $this->parseRedisResult($contentSearchResult)
        ],
            $k,
            'id',
            'distance',
            $distanceLimit
        );

        return $result;
    }

    /**
     * @param array $raw
     * @return array
     */
    private function parseRedisResult(array $raw): array
    {
        $result = [];
        for ($i = 1; $i < count($raw); $i += 2) {

            $data = is_array($raw[$i + 1]) ? $raw[$i + 1] : [];

            $keys = [];
            $values = [];
            foreach($data as $key => $value) {
                if($key % 2 == 0) {
                    $keys[] = $value;
                }
                else {
                    $values[] = $value;
                }
            }

            $result[] = array_combine($keys, $values);
        }

        return $result;
    }

    /**
     * @param array $results
     * @param int $k
     * @param string $idField
     * @param string $distanceField
     * @param float $distanceLimit
     * @return array
     */
    private function unifyResults(array $results, int $k, string $idField = 'id', string $distanceField = 'distance', float $distanceLimit = 0.99): array
    {
        $haystack = [];

        foreach ($results as $result) {
            foreach ($result as $row) {
                if(
                    $distanceLimit >= floatval($row[$distanceField]) &&
                    (!isset($haystack[$row[$idField]]) || $haystack[$row[$idField]][$distanceField] > $row[$distanceField])
                ) {
                    $haystack[$row[$idField]] = $row;
                }
            }
        }

        $haystack = array_values($haystack);
        array_multisort(array_column($haystack, $distanceField), SORT_ASC, $haystack);
        $haystack = array_slice($haystack, 0, $k);

        $haystack = array_map(function($row) {
            return $this->denormalizer->denormalize($row, SearchResponse::class);
        }, $haystack);

        return $haystack;
    }

    /**
     * @param int $vectorDimension
     */
    private function createIndex(int $vectorDimension): void
    {
        /*
        try {
            $this->client->ftdropindex(self::INDEX);
        } catch (ServerException $e) {

        }
        */

        try {
            $schema = [
                new TextField('$.type', 'type'),
                new NumericField('$.id', 'id'),
                new VectorField('$.title_embedding', 'FLAT', [
                    'DIM', $vectorDimension,
                    'TYPE', 'FLOAT32',
                    'DISTANCE_METRIC', 'L2',
                ], 'title_embedding'),
                new VectorField('$.content_embedding', 'FLAT', [
                    'DIM', $vectorDimension,
                    'TYPE', 'FLOAT32',
                    'DISTANCE_METRIC', 'L2',
                ], 'content_embedding'),
            ];

            $this->client->ftcreate(self::INDEX, $schema,
                (new CreateArguments())
                    ->on('JSON')
                    ->prefix([self::ROOT.self::DELIMITER])
            );

        } catch (ServerException $e) {
            //dd($e);
        }
    }
}
