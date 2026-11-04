<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\RoomMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RoomMemberRepository extends ServiceEntityRepository
{
    private const ONLINE_TIMEOUT = 30;

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

    public function findOnlineMembers(Room $room): array
    {
        $threshold = new \DateTimeImmutable('-' . self::ONLINE_TIMEOUT . ' seconds');

        return $this->createQueryBuilder('rm')
            ->andWhere('rm.room = :room')
            ->andWhere('rm.lastSeen >= :threshold')
            ->setParameter('room', $room)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function isOnline(RoomMember $member): bool
    {
        $lastSeen = $member->getLastSeen();

        if ($lastSeen === null) {
            return false;
        }

        return $lastSeen >= new \DateTimeImmutable(
                '-' . self::ONLINE_TIMEOUT . ' seconds'
            );
    }

    public function findMembersWithOnlineStatus(Room $room): array
    {
        $members = $this->findBy(['room' => $room]);

        return array_map(
            fn (RoomMember $member) => [
                'member' => $member,
                'isOnline' => $this->isOnline($member),
            ],
            $members
        );
    }

    public function findExpiredOnlineMembers(): array
    {
        $threshold = new \DateTimeImmutable('-30 seconds');

        return $this->createQueryBuilder('rm')
            ->andWhere('rm.isOnline = :online')
            ->andWhere(
                'rm.lastSeen IS NULL OR rm.lastSeen < :threshold'
            )
            ->setParameter('online', true)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }
}
