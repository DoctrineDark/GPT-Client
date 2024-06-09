<?php

namespace App\Entity;

use App\Repository\GptRequestHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GptRequestHistoryRepository::class)
 * @ORM\Table(name="gpt_request_history")
 * @ORM\HasLifecycleCallbacks()
 */
class GptRequestHistory
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
    private $model;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetime;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $system_message;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $user_message;

    /**
     * @ORM\Column(type="text")
     */
    private $assistant_message;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $prompt_tokens;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $completion_tokens;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $total_tokens;

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

        $this->created_at = $date;
        $this->updated_at = $date;
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

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getSystemMessage(): ?string
    {
        return $this->system_message;
    }

    public function setSystemMessage(?string $system_message): self
    {
        $this->system_message = $system_message;

        return $this;
    }

    public function getUserMessage(): ?string
    {
        return $this->user_message;
    }

    public function setUserMessage(?string $user_message): self
    {
        $this->user_message = $user_message;

        return $this;
    }

    public function getAssistantMessage(): ?string
    {
        return $this->assistant_message;
    }

    public function setAssistantMessage(string $assistant_message): self
    {
        $this->assistant_message = $assistant_message;

        return $this;
    }

    public function getPromptTokens(): ?int
    {
        return $this->prompt_tokens;
    }

    public function setPromptTokens(?int $prompt_tokens): self
    {
        $this->prompt_tokens = $prompt_tokens;

        return $this;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completion_tokens;
    }

    public function setCompletionTokens(?int $completion_tokens): self
    {
        $this->completion_tokens = $completion_tokens;

        return $this;
    }

    public function getTotalTokens(): ?int
    {
        return $this->total_tokens;
    }

    public function setTotalTokens(?int $total_tokens): self
    {
        $this->total_tokens = $total_tokens;

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
