<?php

namespace App\Entity;

use App\Repository\CloudflareIndexRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CloudflareIndexRepository::class)
 * @ORM\Table(name="cloudflare_indexes")
 * @ORM\HasLifecycleCallbacks()
 */
class CloudflareIndex
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
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $dimensions;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $metric;

    /**
     * @ORM\OneToMany(targetEntity=CloudflareVector::class, mappedBy="cloudflareIndex", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $cloudflareVectors;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDimensions(): ?int
    {
        return $this->dimensions;
    }

    public function setDimensions(int $dimensions): self
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getMetric(): ?string
    {
        return $this->metric;
    }

    public function setMetric(string $metric): self
    {
        $this->metric = $metric;

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
            $cloudflareVector->setCloudflareIndex($this);
        }

        return $this;
    }

    public function removeCloudflareVector(CloudflareVector $cloudflareVector): self
    {
        if ($this->cloudflareVectors->removeElement($cloudflareVector)) {
            // set the owning side to null (unless already changed)
            if ($cloudflareVector->getCloudflareIndex() === $this) {
                $cloudflareVector->setCloudflareIndex(null);
            }
        }

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
