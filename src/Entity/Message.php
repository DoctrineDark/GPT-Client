<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 * @ORM\Table(name="messages")
 * @ORM\HasLifecycleCallbacks()
 */
class Message
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
     * @ORM\Column(type="integer", nullable=true)
     */
    private $external_user_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $external_staff_id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content_html;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message_type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sent_at;

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

    public function getExternalId(): ?int
    {
        return $this->external_id;
    }

    public function setExternalId(?int $external_id): self
    {
        $this->external_id = $external_id;

        return $this;
    }

    public function getExternalUserId(): ?int
    {
        return $this->external_user_id;
    }

    public function setExternalUserId(?int $external_user_id): self
    {
        $this->external_user_id = $external_user_id;

        return $this;
    }

    public function getExternalStaffId(): ?int
    {
        return $this->external_staff_id;
    }

    public function setExternalStaffId(?int $external_staff_id): self
    {
        $this->external_staff_id = $external_staff_id;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentHtml(): ?string
    {
        return $this->content_html;
    }

    public function setContentHtml(?string $content_html): self
    {
        $this->content_html = $content_html;

        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->message_type;
    }

    public function setMessageType(?string $message_type): self
    {
        $this->message_type = $message_type;

        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sent_at;
    }

    public function setSentAt(?\DateTimeInterface $sent_at): self
    {
        $this->sent_at = $sent_at;

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
