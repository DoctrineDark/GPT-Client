<?php

namespace App\Entity;

use App\Repository\KnowledgebaseSectionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=KnowledgebaseSectionRepository::class)
 * @ORM\Table(name="knowledgebase_sections")
 * @ORM\HasLifecycleCallbacks()
 */
class KnowledgebaseSection
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable="true")
     */
    private $externalId;

    /**
     * @ORM\ManyToOne(targetEntity=KnowledgebaseCategory::class, inversedBy="sections")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     */
    private $category;

    /**
     * @ORM\Column(type="integer")
     */
    private $externalCategoryId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sectionTitle;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $sectionDescription;

    /**
     * @ORM\OneToMany(targetEntity=Article::class, mappedBy="section", orphanRemoval=true)
     */
    private $articles;

    /**
     * @ORM\Column(type="boolean")
     */
    private $active;

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

        $this->createdAt = $this->createdAt ?? $date;
        $this->updatedAt = $this->updatedAt ?? $date;
    }

    /**
     * On update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime("now");
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(int $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return KnowledgebaseCategory
     */
    public function getCategory(): ?KnowledgebaseCategory
    {
        return $this->category;
    }

    /**
     * @param KnowledgebaseCategory $category
     * @return KnowledgebaseSection
     */
    public function setCategory(KnowledgebaseCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getExternalCategoryId(): ?int
    {
        return $this->externalCategoryId;
    }

    public function setExternalCategoryId(int $externalCategoryId): self
    {
        $this->externalCategoryId = $externalCategoryId;

        return $this;
    }

    public function getSectionTitle(): ?string
    {
        return $this->sectionTitle;
    }

    public function setSectionTitle(?string $sectionTitle): self
    {
        $this->sectionTitle = $sectionTitle;

        return $this;
    }

    public function getSectionDescription(): ?string
    {
        return $this->sectionDescription;
    }

    public function setSectionDescription(?string $sectionDescription): self
    {
        $this->sectionDescription = $sectionDescription;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
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
