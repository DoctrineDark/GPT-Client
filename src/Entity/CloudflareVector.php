<?php

namespace App\Entity;

use App\Repository\CloudflareVectorRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CloudflareVectorRepository::class)
 * @ORM\Table(name="cloudflare_vectors")
 * @ORM\HasLifecycleCallbacks()
 */
class CloudflareVector
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $vectorId;

    /**
     * @ORM\ManyToOne(targetEntity=CloudflareIndex::class, inversedBy="cloudflareVectors")
     */
    private $cloudflareIndex;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="cloudflareVectors")
     */
    private $article;

    /**
     * @ORM\ManyToOne(targetEntity=ArticleParagraph::class, inversedBy="cloudflareVectors")
     */
    private $articleParagraph;

    /**
     * @ORM\ManyToOne(targetEntity=Template::class, inversedBy="cloudflareVectors")
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
        $date = new \DateTime("now");

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

    public function setVectorId(string $vectorId): self
    {
        $this->vectorId = $vectorId;

        return $this;
    }

    public function getCloudflareIndex(): ?CloudflareIndex
    {
        return $this->cloudflareIndex;
    }

    public function setCloudflareIndex(?CloudflareIndex $cloudflareIndex): self
    {
        $this->cloudflareIndex = $cloudflareIndex;

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

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
