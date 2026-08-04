<?php

namespace App\Entity;

use App\Repository\ApplicationsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationsRepository::class)]
class Applications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $cvUsed = null;

    #[ORM\Column(length: 255)]
    private ?string $letterUsed = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'Apllications')]
    private ?Profils $profils = null;

    #[ORM\ManyToOne(inversedBy: 'applications')]
    private ?Companies $companies = null;

    public function __construct()
    {
        $this->sentAt = new \DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCvUsed(): ?string
    {
        return $this->cvUsed;
    }

    public function setCvUsed(string $cvUsed): static
    {
        $this->cvUsed = $cvUsed;

        return $this;
    }

    public function getLetterUsed(): ?string
    {
        return $this->letterUsed;
    }

    public function setLetterUsed(string $letterUsed): static
    {
        $this->letterUsed = $letterUsed;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getProfils(): ?Profils
    {
        return $this->profils;
    }

    public function setProfils(?Profils $profils): static
    {
        $this->profils = $profils;

        return $this;
    }

    public function getCompanies(): ?Companies
    {
        return $this->companies;
    }

    public function setCompanies(?Companies $companies): static
    {
        $this->companies = $companies;

        return $this;
    }
}
