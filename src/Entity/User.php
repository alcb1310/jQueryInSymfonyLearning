<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    security:"is_granted('ROLE_USER')",
    itemOperations:[
        'get',
        'put' => [
            "security"=>"is_granted('ROLE_USER') and object == user ",
        ],
        'delete'=> [
            "security"=>"is_granted('ROLE_ADMIN')",
        ],
    ],
    collectionOperations:[
        'get', 
        'post' => [
            'security' => "is_granted('PUBLIC_ACCESS')",
            'validation_groups' => [
                'Default', 'create'
            ]
        ]
    ],
    normalizationContext:[
        'groups' => ['user:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext:[
        'groups' => ['user:write'],
        'swagger_definition_name' => 'Write',
    ],
)]
#[UniqueEntity(
    fields:'username'
)]
#[UniqueEntity(
    fields:'email'
)]
#[ApiFilter(
    PropertyFilter::class
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups([
        'user:read',
        'user:write'
    ])]
    #[NotBlank()]
    #[Email()]
    private $email;

    #[ORM\Column(type: 'json')]
    #[Groups([
        'admin:write'
    ])]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    private $password;

    #[ORM\Column(type: 'string', length: 255, unique:true)]
    #[Groups([
        'user:read',
        'user:write'
    ])]
    #[NotBlank()]
    private $username;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: CheeseListing::class, cascade:['persist'], orphanRemoval:true)]
    #[Groups([
        'user:write'
    ])]
    #[Valid()]
    private $cheeseListings;

    #[Groups([
        'user:write',
    ])]
    #[SerializedName('password')]
    #[NotBlank([
        'groups' => ['create']
    ])]
    private $plainPassword;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups([
        'user:write',
        'admin:read',
        'owner:read'
    ])]
    private $phoneNumber;

    public function __construct()
    {
        $this->cheeseListings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, CheeseListing>
     */
    public function getCheeseListings(): Collection
    {
        return $this->cheeseListings;
    }

    /**
     * @return Collection\CheeseListing[]
     */
    #[Groups([
        'user:read'
    ])]
    #[SerializedName('cheeseListings')]
    public function getPublishedCheeseListings(): Collection
    {
        return $this->cheeseListings->filter(function(CheeseListing $cheeseListing){
            return $cheeseListing->getIsPublished();
        });
    }

    public function addCheeseListing(CheeseListing $cheeseListing): self
    {
        if (!$this->cheeseListings->contains($cheeseListing)) {
            $this->cheeseListings[] = $cheeseListing;
            $cheeseListing->setOwner($this);
        }

        return $this;
    }

    public function removeCheeseListing(CheeseListing $cheeseListing): self
    {
        if ($this->cheeseListings->removeElement($cheeseListing)) {
            // set the owning side to null (unless already changed)
            if ($cheeseListing->getOwner() === $this) {
                $cheeseListing->setOwner(null);
            }
        }

        return $this;
    }

    public function setPlainPassword(string $password): self
    {
        $this->plainPassword = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
