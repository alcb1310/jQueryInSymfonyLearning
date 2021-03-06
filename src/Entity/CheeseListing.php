<?php

namespace App\Entity;

use Carbon\Carbon;
use App\Validator\IsValidOwner;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiFilter;
use App\Repository\CheeseListingRepository;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;

#[ORM\Entity(repositoryClass: CheeseListingRepository::class)]
#[ORM\EntityListeners([
    'App\Doctrine\CheeseListingSetOwnerListener'
])]
#[ApiResource(
    collectionOperations:[
        'get',
        'post'=> [
            "security"=>"is_granted('ROLE_USER')",
        ],
    ],
    itemOperations:[
        'get' =>[
            'normalization_context' => [
                'groups' => [
                    'cheese_listing:read',
                    'cheese_listing:item:get'
                ]
            ]
        ], 
        'put' => [
            "security"=>"is_granted('EDIT', object)",
            'security_message' => 'Only the creator can edit a cheese listing'
        ],
        'delete' => [
            "security"=>"is_granted('ROLE_ADMIN')",
        ],
    ],
    shortName:'cheeses',
    normalizationContext:[
        'groups' => ['cheese_listing:read']
    ],
    denormalizationContext:[
        'groups' => ['cheese_listing:write']
    ],
    attributes:[
        'pagination_items_per_page' => 10,
        'formats' => [
            'jsonld', 
            'json', 
            'html', 
            'csv' => 'text/csv'
        ],
    ]
)]
#[ApiFilter(
    BooleanFilter::class,
    properties: ['isPublished'],
)]
#[ApiFilter(
    SearchFilter::class,
    properties:[
        'title' => 'partial',
        'owner' => 'exact', 
        'owner.username' => 'partial'
    ]
)]
#[ApiFilter(
    RangeFilter::class,
    properties: ['price'],
)]
#[ApiFilter(
    PropertyFilter::class
)]
class CheeseListing
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type:'integer')]
    private $id;

    #[ORM\Column(type:'string', length:255)]
    #[NotBlank()]
    #[Length(
        min:2,
        max:50,
        maxMessage:'Describe your cheese in  50 characters or less'
    )]
    #[Groups([
        'cheese_listing:read',
        'cheese_listing:write',
        'user:read',
        'user:write'
    ])]
    private $title;

    #[ORM\Column(type:'text')]
    #[NotBlank()]
    #[Groups([
        'cheese_listing:read'
    ])]
    private $description;

    #[ORM\Column(type:'integer')]
    #[NotBlank()]
    #[Groups([
        'cheese_listing:read',
        'cheese_listing:write',
        'user:read',
        'user:write'
    ])]
    #[ApiProperty([
        'description' => 'The price of this delicious cheese, in cents'
    ])]
    private $price;

    #[ORM\Column(type:'datetime')]
    private $createdAt;

    #[orm\Column(type:'boolean')]
    private $isPublished = false;

    /**
     * @IsValidOwner()
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'cheeseListings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups([
        'cheese_listing:read',
        'cheese_listing:write'
    ])]
    private $owner;

    public function __construct(string $title=null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->title = $title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    #[Groups([
        'cheese_listing:read'
    ])]
    #[ApiProperty([
        'description' => 'Returns a description with maximum 40 characters'
    ])]
    #[SerializedName('short description')]
    public function getShortDescription(): ?string
    {   
        if (strlen($this->description) < 40) {
            return $this->description;
        }
        return substr($this->description, 0, 40).'...';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ApiProperty([
        'description' => 'The description of the cheese as raw text'
    ])]
    #[Groups([
        'cheese_listing:write',
        'user:write'
    ])]
    #[SerializedName('description')]
    public function setTextDescription(string $description): self
    {
        $this->description = nl2br($description);

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * How long ago in text that this cheese listing was added.
     *
     * @Groups("cheese_listing:read")
     */
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->getCreatedAt())->diffForHumans();
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
