<?php

namespace App\Entity;

use App\Repository\ProfilsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilsRepository::class)]
class Profils
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(length: 50)]
    private ?string $city = null;

    #[ORM\Column(length: 50)]
    private ?string $defaultCv = null;

    #[ORM\Column(length: 50)]
    private ?string $defaultLetter = null;

    #[ORM\Column(length: 100)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $website = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(mappedBy: 'Profils', cascade: ['persist', 'remove'])]
    private ?Users $users = null;

    /**
     * @var Collection<int, Applications>
     */
    #[ORM\OneToMany(targetEntity: Applications::class, mappedBy: 'profils')]
    private Collection $Apllications;

    /**
     * @var Collection<int, Notifications>
     */
    #[ORM\OneToMany(targetEntity: Notifications::class, mappedBy: 'profils')]
    private Collection $notifications;

    /**
     * @var Collection<int, ContactsAdmin>
     */
    #[ORM\OneToMany(targetEntity: ContactsAdmin::class, mappedBy: 'profils')]
    private Collection $contactsAdmin;

    public function __construct()
    {
        $this->Apllications = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->contactsAdmin = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getDefaultCv(): ?string
    {
        return $this->defaultCv;
    }

    public function setDefaultCv(string $defaultCv): static
    {
        $this->defaultCv = $defaultCv;

        return $this;
    }

    public function getDefaultLetter(): ?string
    {
        return $this->defaultLetter;
    }

    public function setDefaultLetter(string $defaultLetter): static
    {
        $this->defaultLetter = $defaultLetter;

        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(string $linkedin): static
    {
        $this->linkedin = $linkedin;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUsers(): ?Users
    {
        return $this->users;
    }

    public function setUsers(?Users $users): static
    {
        // unset the owning side of the relation if necessary
        if ($users === null && $this->users !== null) {
            $this->users->setProfils(null);
        }

        // set the owning side of the relation if necessary
        if ($users !== null && $users->getProfils() !== $this) {
            $users->setProfils($this);
        }

        $this->users = $users;

        return $this;
    }

    /**
     * @return Collection<int, Applications>
     */
    public function getApllications(): Collection
    {
        return $this->Apllications;
    }

    public function addApllication(Applications $apllication): static
    {
        if (!$this->Apllications->contains($apllication)) {
            $this->Apllications->add($apllication);
            $apllication->setProfils($this);
        }

        return $this;
    }

    public function removeApllication(Applications $apllication): static
    {
        if ($this->Apllications->removeElement($apllication)) {
            // set the owning side to null (unless already changed)
            if ($apllication->getProfils() === $this) {
                $apllication->setProfils(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Notifications>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notifications $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setProfils($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getProfils() === $this) {
                $notification->setProfils(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ContactsAdmin>
     */
    public function getContactsAdmin(): Collection
    {
        return $this->contactsAdmin;
    }

    public function addContactsAdmin(ContactsAdmin $contactsAdmin): static
    {
        if (!$this->contactsAdmin->contains($contactsAdmin)) {
            $this->contactsAdmin->add($contactsAdmin);
            $contactsAdmin->setProfils($this);
        }

        return $this;
    }

    public function removeContactsAdmin(ContactsAdmin $contactsAdmin): static
    {
        if ($this->contactsAdmin->removeElement($contactsAdmin)) {
            // set the owning side to null (unless already changed)
            if ($contactsAdmin->getProfils() === $this) {
                $contactsAdmin->setProfils(null);
            }
        }

        return $this;
    }
}
