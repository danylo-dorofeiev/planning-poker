<?php

namespace App\Service;

use App\Entity\RoomMember;
use App\Repository\RoomMemberRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class RoomMemberStatusChecker
{
    public function __construct(
        private RoomMemberRepository $roomMemberRepository,
        private EntityManagerInterface $entityManager,
        private RoomMemberStatusPublisher $publisher,
    ) {}

    /**
     * @return array{members: int, rooms: int}
     */
    public function check(): array
    {
        $threshold = new \DateTimeImmutable('-30 seconds');

        $members = $this->roomMemberRepository->findExpiredOnlineMembers();

        if (empty($members)) {
            return ['members' => 0, 'rooms' => 0];
        }

        $affectedRooms = [];

        foreach ($members as $member) {
            $room = $member->getRoom();
            $affectedRooms[$room->getUuid()->toRfc4122()] = $room;
        }

        $updated = $this->entityManager->createQueryBuilder()
            ->update(RoomMember::class, 'rm')
            ->set('rm.isOnline', ':offline')
            ->where('rm.id IN (:ids)')
            ->andWhere('rm.isOnline = :online')
            ->andWhere('(rm.lastSeen IS NULL OR rm.lastSeen < :threshold)')
            ->setParameter('offline', false)
            ->setParameter('online', true)
            ->setParameter('ids', array_map(
                fn (RoomMember $member) => $member->getId(),
                $members
            ))
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();

        if ($updated > 0) {
            foreach ($affectedRooms as $room) {
                $this->publisher->publish($room);
            }
        }

        return [
            'members' => $updated,
            'rooms' => count($affectedRooms),
        ];
    }
}
