<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class TicketService
{
    public function __construct(
        private TicketRepository        $ticketRepository,
        private EntityManagerInterface  $entityManager,
    ) {
    }

    public function findAll(): array
    {
        return $this->ticketRepository->findAll();
    }

    public function createTicket(Ticket $ticket): Ticket
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        return $ticket;
    }

    public function updateTicket(Ticket $ticket): Ticket
    {
        $ticket->setUpdatedAt(new \DateTimeImmutable);
        $this->entityManager->flush();

        return $ticket;
    }

    public function deleteTicket(Ticket $ticket): Ticket
    {
        $this->entityManager->remove($ticket);
        $this->entityManager->flush();

        return $ticket;
    }
}
