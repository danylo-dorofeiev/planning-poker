<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Room;
use App\Entity\RoomMember;
use App\Repository\RoomMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class RoomMemberService
{
    public function __construct(
        private RoomMemberRepository    $roomMemberRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function findMember(Room $room, User $user): ?RoomMember
    {
        return $this->roomMemberRepository->findMember($room, $user);
    }

    public function createRoomMember(RoomMember $roomMember): RoomMember
    {
        $this->entityManager->persist($roomMember);
        $this->entityManager->flush();

        return $roomMember;
    }

    public function updateRoomMember(RoomMember $roomMember): RoomMember
    {
        $this->entityManager->flush();

        return $roomMember;
    }

    public function deleteRoom(RoomMember $roomMember): RoomMember
    {
        $this->entityManager->remove($roomMember);
        $this->entityManager->flush();

        return $roomMember;
    }
}
