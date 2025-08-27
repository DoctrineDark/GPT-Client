<?php

namespace App\Entity;

use App\Repository\OpenSearchIndexRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OpenSearchIndexRepository::class)
 * @ORM\Table(name="opensearch_indexes")
 * @ORM\HasLifecycleCallbacks()
 */
class OpenSearchIndex
{
    public const TEXT_PROPERTY = 'text';
    public const EMBEDDING_PROPERTY = 'embedding';

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
     * @ORM\Column(type="integer")
     */
    private $dimensions;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $analyzer;

    /**
     * @ORM\OneToMany(targetEntity=OpenSearchVector::class, mappedBy="openSearchIndex", orphanRemoval=true, cascade={"persist", "remove"})
     */
    private $openSearchVectors;

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
        $this->openSearchVectors = new ArrayCollection();
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

    public function getDimensions(): ?int
    {
        return $this->dimensions;
    }

    public function setDimensions(int $dimensions): self
    {
        $this->dimensions = $dimensions;

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

    public function getAnalyzer(): ?string
    {
        return $this->analyzer;
    }

    public function setAnalyzer(?string $analyzer): self
    {
        $this->analyzer = $analyzer;

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

    /**
     * @return Collection<int, OpenSearchVector>
     */
    public function getOpenSearchVectors(): Collection
    {
        return $this->openSearchVectors;
    }

    public function addOpenSearchVector(OpenSearchVector $openSearchVector): self
    {
        if (!$this->openSearchVectors->contains($openSearchVector)) {
            $this->openSearchVectors[] = $openSearchVector;
            $openSearchVector->setOpenSearchIndex($this);
        }

        return $this;
    }

    public function removeOpenSearchVector(OpenSearchVector $openSearchVector): self
    {
        if ($this->openSearchVectors->removeElement($openSearchVector)) {
            // set the owning side to null (unless already changed)
            if ($openSearchVector->getOpenSearchIndex() === $this) {
                $openSearchVector->setOpenSearchIndex(null);
            }
        }

        return $this;
    }

    /**/

    public function buildOptions(): array
    {
        return [
            'settings' => [
                'index' => [
                    'knn' => true
                ]
            ],
            'mappings' => [
                'properties' => [
                    'id' => [
                        'type' => 'keyword'
                    ],
                    self::TEXT_PROPERTY => [
                        'type' => 'text'
                    ],
                    self::EMBEDDING_PROPERTY => [
                        'type' => 'knn_vector',
                        'dimension' => $this->dimensions
                    ],
                    'metadata' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'keyword'
                            ],
                            'type' => [
                                'type' => 'keyword'
                            ],
                            'title' => [
                                'type' => 'keyword'
                            ],
                            'content' => [
                                'type' => 'keyword'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
