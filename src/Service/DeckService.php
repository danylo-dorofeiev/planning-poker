<?php

namespace App\Service;

use App\Entity\Deck;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class DeckService
{
    public function __construct(
        private DeckRepository          $deckRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function findAll(): array
    {
        return $this->deckRepository->findAll();
    }

    public function createDeck(Deck $deck): Deck
    {
        $this->entityManager->persist($deck);
        $this->entityManager->flush();

        return $deck;
    }

    public function updateDeck(Deck $deck): Deck
    {
        $this->entityManager->flush();

        return $deck;
    }

    public function deleteDeck(Deck $deck): Deck
    {
        $this->entityManager->remove($deck);
        $this->entityManager->flush();

        return $deck;
    }
}
