<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Round;
use App\Entity\Ticket;
use App\Entity\Vote;
use App\Enum\RoomStatus;
use App\Enum\RoundStatus;
use App\Enum\TicketStatus;
use App\Event\RoomEditedEvent;
use App\Event\RoundRevealedEvent;
use App\Event\RoundStartedEvent;
use App\Event\TicketEditedEvent;
use App\Repository\CardRepository;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use App\Repository\VoteRepository;
use App\Security\Voter\RoomVoter;
use App\Service\RoomService;
use App\Service\RoundService;
use App\Service\TicketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class VotingController extends AbstractController
{
    #[Route('/room/{uuid}/ticket/{id}/round/start', name: 'round_start')]
    public function start(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, TicketRepository $ticketRepository, RoomService $roomService, TicketService $ticketService, RoundService $roundService, EventDispatcherInterface $eventDispatcher): Response {
        $this->denyAccessUnlessGranted(RoomVoter::START, $room);

        $votingTicket = $ticketRepository->findVotingTicket($room);
        if($votingTicket !== null) {
            throw $this->createAccessDeniedException(
                'Another ticket is currently voted for this room.'
            );
        }

        $round = new Round();
        $round->setNumber($ticket->getRounds()->count() + 1);
        $round->setStatus(RoundStatus::ACTIVE);

        foreach ($room->getDeck()->getCards() as $card) {
            $round->addCard($card);
        }

        $ticket->addRound($round);
        $ticket->setStatus(TicketStatus::VOTING);
        $ticket->setUpdatedAt();

        $room->setStatus(RoomStatus::VOTING);
        $room->setUpdatedAt();

        $roundService->createRound($round);
        $ticketService->updateTicket($ticket);
        $roomService->updateRoom($room);

        $eventDispatcher->dispatch(
            new TicketEditedEvent($ticket),
        );

        $eventDispatcher->dispatch(
            new RoomEditedEvent($room),
        );

        $eventDispatcher->dispatch(
            new RoundStartedEvent($round),
        );

        return new Response(status: 204);
    }

    #[Route('/room/{uuid}/ticket/{id}/round/vote', name: 'round_vote')]
    public function vote(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, Round $round, RoundRepository $roundRepository, CardRepository $cardRepository, VoteRepository $voteRepository, Request $request, EntityManagerInterface $entityManager): Response {
        $round = $roundRepository->findOneBy([
            'ticket' => $ticket,
            'status' => RoundStatus::ACTIVE,
        ]);

        if ($round->getStatus() !== RoundStatus::ACTIVE) {
            throw $this->createAccessDeniedException();
        }

        $card = $cardRepository->find($request->request->get('card'));

        if(!$card || !$round->getCards()->contains($card)) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        $vote = $voteRepository->findOneBy([
            'round' => $round,
            'user' => $user,
        ]);

        if(!$vote) {
            $vote = new Vote();

            $vote->setRound($round);
            $vote->setUser($user);

            $entityManager->persist($vote);

        }

        $vote->setValue($card->getValue());
        $entityManager->flush();

        return new Response(status: 204);
    }

    #[Route('/room/{uuid}/ticket/{id}/round/reveal', name: 'round_reveal', methods: ['POST'])]
    public function reveal(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, RoundRepository $roundRepository, TicketService $ticketService, RoundService $roundService, EntityManagerInterface $entityManager, HubInterface $hub, EventDispatcherInterface $eventDispatcher,): Response {
        $this->denyAccessUnlessGranted(RoomVoter::REVEAL, $room);

        $round = $roundRepository->findOneBy([
            'ticket' => $ticket,
            'status' => RoundStatus::ACTIVE,
        ]);

        if (!$round) {
            return new Response('No active round', 400);
        }

        $round->setStatus(RoundStatus::REVEALED);

        $ticket->setStatus(TicketStatus::FINISHED);
        $ticket->setUpdatedAt();

        $ticketService->updateTicket($ticket);
        $roundService->updateRound($round);

        $eventDispatcher->dispatch(
            new TicketEditedEvent($ticket)
        );

        $eventDispatcher->dispatch(
            new RoundRevealedEvent($round)
        );

        return new Response(status: 204);
    }
}
