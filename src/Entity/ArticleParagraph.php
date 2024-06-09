<?php

namespace App\Entity;

use App\Repository\ArticleParagraphRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticleParagraphRepository::class)
 * @ORM\Table(name="article_paragraphs")
 * @ORM\HasLifecycleCallbacks()
 */
class ArticleParagraph
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Article::class, inversedBy="paragraphs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $article;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $paragraph_title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $paragraph_content;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * On insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $date = new \DateTime("now");

        $this->created_at = $this->created_at ?? $date;
        $this->updated_at = $this->updated_at ?? $date;
    }

    /**
     * On update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updated_at = new \DateTime("now");
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParagraphTitle(): ?string
    {
        return $this->paragraph_title;
    }

    public function setParagraphTitle(?string $paragraph_title): self
    {
        $this->paragraph_title = $paragraph_title;

        return $this;
    }

    public function getParagraphContent(): ?string
    {
        return $this->paragraph_content;
    }

    public function setParagraphContent(?string $paragraph_content): self
    {
        $this->paragraph_content = $paragraph_content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
