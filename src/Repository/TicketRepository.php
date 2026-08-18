<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findAllByRoomOwner(User $user): array
    {
        return $this->createQueryBuilder('ticket')
            ->join('ticket.room', 'room')
            ->where('room.owner = :owner')
            ->setParameter('owner', $user)
            ->orderBy('ticket.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findVotingTicket(Room $room): ?Ticket
    {
        return $this->createQueryBuilder('ticket')
            ->andwhere('ticket.room = :room')
            ->andWhere('ticket.status = :status')
            ->setParameter('room', $room)
            ->setParameter('status', TicketStatus::VOTING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
