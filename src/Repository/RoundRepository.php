<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\Round;
use App\Enum\RoundStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Round>
 */
class RoundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Round::class);
    }

    public function findActiveRoundByRoom(Room $room)
    {
        return $this->createQueryBuilder('round')
            ->join('round.ticket', 'ticket')
            ->andWhere('ticket.room = :room')
            ->andWhere('round.status = :status')
            ->setParameter('room', $room)
            ->setParameter('status', RoundStatus::ACTIVE)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
