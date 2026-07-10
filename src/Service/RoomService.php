<?php

namespace App\Service;

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

class RoomService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }
    public function createRoom(Room $room): Room
    {
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        return $room;
    }
}
