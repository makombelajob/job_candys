<?php

namespace App\Entity;

use App\Repository\VisitorsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VisitorsRepository::class)]
class Visitors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?int $visitCount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $firstVisitAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $lastVisitAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getVisitCount(): ?int
    {
        return $this->visitCount;
    }

    public function setVisitCount(int $visitCount): static
    {
        $this->visitCount = $visitCount;

        return $this;
    }

    public function getFirstVisitAt(): ?\DateTimeImmutable
    {
        return $this->firstVisitAt;
    }

    public function setFirstVisitAt(\DateTimeImmutable $firstVisitAt): static
    {
        $this->firstVisitAt = $firstVisitAt;

        return $this;
    }

    public function getLastVisitAt(): ?\DateTimeImmutable
    {
        return $this->lastVisitAt;
    }

    public function setLastVisitAt(\DateTimeImmutable $lastVisitAt): static
    {
        $this->lastVisitAt = $lastVisitAt;

        return $this;
    }
}
