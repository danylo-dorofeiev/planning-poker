<?php

namespace App\Repository;

use App\Entity\Round;
use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vote::class);
    }

    public function findVotedUserIds(Round $round): array
    {
        return $this->createQueryBuilder('vote')
            ->select('IDENTITY(vote.user) AS userId')
            ->where('vote.round = :round')
            ->setParameter('round', $round)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
