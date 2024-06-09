<?php

namespace App\Service\VectorSearch;

class Embedding implements \JsonSerializable
{
    /** @var int */
    private $id;

    /** @var string */
    private $type;

    /** @var array|null */
    private $titleEmbedding;

    /** @var array|null */
    private $contentEmbedding;

    public function __construct(int $id, string $type, ?array $titleEmbedding=null, ?array $contentEmbedding=null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->titleEmbedding = $titleEmbedding;
        $this->contentEmbedding = $contentEmbedding;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Embedding
     */
    public function setId(int $id) : self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Embedding
     */
    public function setType(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getTitleEmbedding()
    {
        return $this->titleEmbedding;
    }

    /**
     * @param array|null $titleEmbedding
     * @return Embedding
     */
    public function setTitleEmbedding(?array $titleEmbedding) : self
    {
        $this->titleEmbedding = $titleEmbedding;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getContentEmbedding()
    {
        return $this->contentEmbedding;
    }

    /**
     * @param array|null $contentEmbedding
     * @return Embedding
     */
    public function setContentEmbedding(?array $contentEmbedding) : self
    {
        $this->contentEmbedding = $contentEmbedding;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title_embedding' => $this->titleEmbedding,
            'content_embedding' => $this->contentEmbedding
        ];
    }
}