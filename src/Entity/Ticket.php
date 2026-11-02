<?php

namespace App\Entity;

use App\Enum\EstimationUnit;
use App\Enum\TicketSource;
use App\Enum\TicketStatus;
use App\Repository\TicketRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(name: 'ticket_key', length: 20, nullable: true)]
    private ?string $key = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: TicketStatus::class)]
    private TicketStatus $status;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $votingResult = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isTimeBased = true;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    #[ORM\OneToMany(targetEntity: Round::class, mappedBy: 'ticket', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['number' => 'ASC'])]
    private Collection $rounds;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(enumType: TicketSource::class)]
    private TicketSource $source;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->status = TicketStatus::PENDING;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->source = TicketSource::LOCAL;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function setStatus(TicketStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getVotingResult(): ?string
    {
        return $this->votingResult;
    }

    public function setVotingResult(?string $votingResult): static
    {
        $this->votingResult = $votingResult;

        return $this;
    }

    public function isTimeBased(): bool
    {
        return $this->isTimeBased;
    }

    public function setIsTimeBased(bool $isTimeBased): static
    {
        $this->isTimeBased = $isTimeBased;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getRounds(): Collection
    {
        return $this->rounds;
    }

    public function addRound(Round $round): static
    {
        if (!$this->rounds->contains($round)) {
            $this->rounds->add($round);
            $round->setTicket($this);
        }

        return $this;
    }

    public function removeRound(Round $round): static
    {
        if ($this->rounds->removeElement($round)) {
            if ($round->getTicket() === $this) {
                $round->setTicket(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSource(): TicketSource
    {
        return $this->source;
    }

    public function setSource(TicketSource $source): static
    {
        $this->source = $source;

        return $this;
    }
}
