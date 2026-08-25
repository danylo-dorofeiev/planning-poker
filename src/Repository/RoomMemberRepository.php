<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RoomMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomMember::class);
    }

    public function findMember(Room $room, User $user): ?RoomMember
    {
        return $this->findOneBy([
            'room' => $room,
            'user' => $user,
        ]);
    }

    public function findOnlineMembers(Room $room, int $seconds = 10): array
    {
        $threshold = new \DateTimeImmutable("-{$seconds} seconds");

        return $this->createQueryBuilder('rm')
            ->andWhere('rm.room = :room')
            ->andWhere('rm.lastSeen >= :threshold')
            ->setParameter('room', $room)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
