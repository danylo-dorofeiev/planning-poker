<?php

namespace App\Service;

use App\Entity\Room;
use App\Entity\User;
use App\Repository\RoomRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class RoomService
{
    public function __construct(
        private RoomRepository          $roomRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function findAllByOwner(User $user): array
    {
        return $this->roomRepository->findAllByOwner($user);
    }

    public function createRoom(Room $room): Room
    {
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }

    public function updateRoom(Room $room): Room
    {
        $this->entityManager->flush();

        return $room;
    }

    public function deleteRoom(Room $room): Room
    {
        $this->entityManager->remove($room);
        $this->entityManager->flush();

        return $room;
    }
}
