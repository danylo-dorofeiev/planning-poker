<?php

namespace App\Service;

use App\Entity\Round;
use App\Entity\User;
use App\Repository\RoundRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class RoundService
{
    public function __construct(
        private RoundRepository          $roundRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function createRound(Round $round): Round
    {
        $this->entityManager->persist($round);
        $this->entityManager->flush();

        return $round;
    }

    public function updateRound(Round $round): Round
    {
        $this->entityManager->flush();

        return $round;
    }

    public function deleteRound(Round $round): Round
    {
        $this->entityManager->remove($round);
        $this->entityManager->flush();

        return $round;
    }
}
