<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ArticleRepository::class)
 * @ORM\Table(name="articles")
 * @ORM\HasLifecycleCallbacks()
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $external_id;

    /**
     * @ORM\ManyToOne(targetEntity=KnowledgebaseSection::class, inversedBy="articles")
     * @ORM\JoinColumn(name="section_id", referencedColumnName="id", nullable=true)
     */
    private $section;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $external_section_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $article_title;

    /**
     * @ORM\OneToMany(targetEntity=ArticleParagraph::class, mappedBy="article", orphanRemoval=true)
     */
    private $paragraphs;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $article_tags;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $access_type;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;


    public function __construct()
    {
        $this->paragraphs = new ArrayCollection();
    }

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

    public function getExternalId(): ?int
    {
        return $this->external_id;
    }

    public function setExternalId(?int $external_id): self
    {
        $this->external_id = $external_id;

        return $this;
    }

    /**
     * @return KnowledgebaseSection
     */
    public function getSection(): ?KnowledgebaseSection
    {
        return $this->section;
    }

    /**
     * @param KnowledgebaseSection $section
     * @return Article
     */
    public function setSection(KnowledgebaseSection$section): self
    {
        $this->section = $section;

        return $this;
    }

    public function getExternalSectionId(): ?int
    {
        return $this->external_section_id;
    }

    public function setExternalSectionId(?int $externalSectionId): self
    {
        $this->external_section_id = $externalSectionId;

        return $this;
    }

    public function getArticleTitle(): ?string
    {
        return $this->article_title;
    }

    public function setArticleTitle(?string $article_title): self
    {
        $this->article_title = $article_title;

        return $this;
    }

    public function getArticleTags(): ?string
    {
        return $this->article_tags;
    }

    public function setArticleTags(?string $article_tags): self
    {
        $this->article_tags = $article_tags;

        return $this;
    }

    public function getAccessType(): ?string
    {
        return $this->access_type;
    }

    public function setAccessType(?string $access_type): self
    {
        $this->access_type = $access_type;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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

    /**
     * @return Collection<int, ArticleParagraph>
     */
    public function getParagraphs(): Collection
    {
        return $this->paragraphs;
    }

    public function addParagraph(ArticleParagraph $paragraph): self
    {
        if (!$this->paragraphs->contains($paragraph)) {
            $this->paragraphs[] = $paragraph;
            $paragraph->setArticle($this);
        }

        return $this;
    }

    public function removeParagraph(ArticleParagraph $paragraph): self
    {
        if ($this->paragraphs->removeElement($paragraph)) {
            // set the owning side to null (unless already changed)
            if ($paragraph->getArticle() === $this) {
                $paragraph->setArticle(null);
            }
        }

        return $this;
    }

    public function getArticleContent() : ?string
    {
        $content = null;

        foreach ($this->getParagraphs() as $paragraph) {
            $paragraphTitle = $paragraph->getParagraphTitle();
            $paragraphContent = $paragraph->getParagraphContent();

            if($paragraphTitle) {
                $content .= $paragraphTitle.PHP_EOL;
            }
            if($paragraphContent) {
                $content .= $paragraphContent;
            }
        }

        return $content;
    }
}
