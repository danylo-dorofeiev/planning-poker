<?php

namespace App\Entity;

use App\Enum\RoomMemberRole;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(
    name: 'room_member',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'unique_room_user',
            columns: ['room_id', 'user_id']
        )
    ]
)]
#[ORM\Entity]
class RoomMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(enumType: RoomMemberRole::class)]
    private RoomMemberRole $role = RoomMemberRole::MEMBER;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSeen = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOnline = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): RoomMemberRole
    {
        return $this->role;
    }

    public function setRole(RoomMemberRole $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getLastSeen(): ?\DateTimeImmutable
    {
        return $this->lastSeen;
    }

    public function setLastSeen(\DateTimeImmutable $lastSeen): static
    {
        $this->lastSeen = $lastSeen;
        return $this;
    }

    public function updateLastSeen(): static
    {
        $this->lastSeen = new \DateTimeImmutable();

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }
}
