<?php

namespace App\Entity;

use App\Repository\KnowledgebaseCategoryRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(repositoryClass=KnowledgebaseCategoryRepository::class)
 * @ORM\Table(name="knowledgebase_categories")
 * @ORM\HasLifecycleCallbacks()
 */
class KnowledgebaseCategory
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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $categoryTitle;

    /**
     * @ORM\OneToMany(targetEntity=KnowledgebaseSection::class, mappedBy="category", orphanRemoval=true)
     */
    private $sections;

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

    public function getCategoryTitle(): ?string
    {
        return $this->categoryTitle;
    }

    public function setCategoryTitle(?string $categoryTitle): self
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    /**
     * @return Collection<int, KnowledgebaseSection>
     */
    public function getSections(): Collection
    {
        return $this->sections;
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
