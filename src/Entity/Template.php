<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemplateRepository::class)
 * @ORM\Table(name="templates")
 * @ORM\HasLifecycleCallbacks()
 */
class Template
{
    public const TYPE = 'template';

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $template_title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $template_content;

    /**
     * @ORM\OneToMany(targetEntity=CloudflareVector::class, mappedBy="template")
     */
    private $cloudflareVectors;

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
        $this->cloudflareVectors = new ArrayCollection();
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

    public function getTemplateTitle(): ?string
    {
        return $this->template_title;
    }

    public function setTemplateTitle(?string $template_title): self
    {
        $this->template_title = $template_title;

        return $this;
    }

    public function getTemplateContent(): ?string
    {
        return $this->template_content;
    }

    public function setTemplateContent(?string $template_content): self
    {
        $this->template_content = $template_content;

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
     * @return Collection<int, CloudflareVector>
     */
    public function getCloudflareVectors(): Collection
    {
        return $this->cloudflareVectors;
    }

    public function addCloudflareVector(CloudflareVector $cloudflareVector): self
    {
        if (!$this->cloudflareVectors->contains($cloudflareVector)) {
            $this->cloudflareVectors[] = $cloudflareVector;
            $cloudflareVector->setTemplate($this);
        }

        return $this;
    }

    public function removeCloudflareVector(CloudflareVector $cloudflareVector): self
    {
        if ($this->cloudflareVectors->removeElement($cloudflareVector)) {
            // set the owning side to null (unless already changed)
            if ($cloudflareVector->getTemplate() === $this) {
                $cloudflareVector->setTemplate(null);
            }
        }

        return $this;
    }
}
