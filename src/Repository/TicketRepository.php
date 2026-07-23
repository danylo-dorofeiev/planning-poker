<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Entity\User;
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
}
