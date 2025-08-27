<?php

namespace App\Entity;

use App\Repository\OpenSearchVectorRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OpenSearchVectorRepository::class)
 * @ORM\Table(name="opensearch_vectors")
 * @ORM\HasLifecycleCallbacks()
 */
class OpenSearchVector
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vectorId;

    /**
     * @ORM\ManyToOne(targetEntity=OpenSearchIndex::class, inversedBy="openSearchVectors")
     * @ORM\JoinColumn(name="opensearch_index_id", referencedColumnName="id", nullable="true")
     */
    private $openSearchIndex;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="openSearchVectors")
     */
    private $article;

    /**
     * @ORM\ManyToOne(targetEntity=ArticleParagraph::class, inversedBy="openSearchVectors")
     */
    private $articleParagraph;

    /**
     * @ORM\ManyToOne(targetEntity=Template::class, inversedBy="openSearchVectors")
     */
    private $template;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * On insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $date = new DateTime("now");

        $this->createdAt = $date;
        $this->updatedAt = $date;
    }

    /**
     * On update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new DateTime("now");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVectorId(): ?string
    {
        return $this->vectorId;
    }

    public function setVectorId(?string $vectorId): self
    {
        $this->vectorId = $vectorId;

        return $this;
    }

    public function getOpenSearchIndex(): ?OpenSearchIndex
    {
        return $this->openSearchIndex;
    }

    public function setOpenSearchIndex(?OpenSearchIndex $openSearchIndex): self
    {
        $this->openSearchIndex = $openSearchIndex;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getArticleParagraph(): ?ArticleParagraph
    {
        return $this->articleParagraph;
    }

    public function setArticleParagraph(?ArticleParagraph $articleParagraph): self
    {
        $this->articleParagraph = $articleParagraph;

        return $this;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
