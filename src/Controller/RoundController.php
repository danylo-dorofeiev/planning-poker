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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[IsGranted('ROLE_USER')]
final class RoundController extends AbstractController
{
    #[Route('/room/{room_id}/ticket/{ticket_id}/round/start', name: 'round_start')]
    public function start(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,

        TicketRepository $ticketRepository,
        RoomService $roomService,
        TicketService $ticketService,
        RoundService $roundService,
        EventDispatcherInterface $eventDispatcher
    ): Response {

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

    #[Route('/room/{room_id}/ticket/{ticket_id}/round/{round_id}/vote', name: 'round_vote', methods: ['POST'])]
    public function vote(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        #[MapEntity(mapping: ['round_id' => 'id'])] Round $round,

        RoundRepository $roundRepository,
        VoteRepository $voteRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ): Response {

        if ($round->getStatus() !== RoundStatus::ACTIVE) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $value = $request->request->get('value');

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

        $vote->setValue($value);
        $entityManager->flush();

        $room->setUpdatedAt();
        $eventDispatcher->dispatch(
            new RoomEditedEvent($room),
        );

        $ticket->setUpdatedAt();
        $eventDispatcher->dispatch(
            new TicketEditedEvent($ticket),
        );

        return new Response(status: 204);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/round/{round_id}/reveal', name: 'round_reveal', methods: ['POST'])]
    public function reveal(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        #[MapEntity(mapping: ['round_id' => 'id'])] Round $round,

        RoundRepository $roundRepository,
        TicketService $ticketService,
        RoundService $roundService,
        EventDispatcherInterface $eventDispatcher
    ): Response {

        $this->denyAccessUnlessGranted(RoomVoter::REVEAL, $room);

        $round = $roundRepository->findOneBy([
            'ticket' => $ticket,
            'status' => RoundStatus::ACTIVE,
        ]);

        if (!$round) {
            return new Response(status: 400);
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
