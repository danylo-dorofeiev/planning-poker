<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Room;
use App\Entity\Round;
use App\Entity\Ticket;
use App\Entity\Vote;
use App\Enum\RoomStatus;
use App\Enum\RoundStatus;
use App\Enum\TicketStatus;
use App\Event\MemberListEvent;
use App\Event\PokerTableEvent;
use App\Event\TicketCardListEvent;
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
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[IsGranted('ROLE_USER')]
final class RoundController extends AbstractController
{
    #[Route('/room/{room_id}/ticket/{ticket_id}/round/{round_id}', name: 'round_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['round_id' => 'id'])] Round $round,
    ): Response {
        return $this->render('ticket/stream/_round_overview.html.twig', [
            'round' => $round,
        ]);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/round/start', name: 'round_start')]
    public function start(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        TicketRepository $ticketRepository,
        RoomService $roomService,
        TicketService $ticketService,
        RoundService $roundService,
        HubInterface $hub,
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

        $round->setCards(
            $room->getDeck()->getCards()
            ->map(fn (Card $card) => $card->getValue())
            ->toArray()
        );

        $ticket->addRound($round);

        $ticket->setStatus(TicketStatus::VOTING);
        $ticket->setUpdatedAt();

        $room->setStatus(RoomStatus::VOTING);
        $room->setUpdatedAt();

        $roundService->createRound($round);
        $ticketService->updateTicket($ticket);
        $roomService->updateRoom($room);

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'member_list',
                    'event' => 'member_list:update',
                    'url' => $this->generateUrl('member_list_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'ticket_list',
                    'event' => 'ticket_list:update',
                    'url' => $this->generateUrl('ticket_list_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'poker_table',
                    'event' => 'poker_table:update',
                    'url' => $this->generateUrl('poker_table_update', [
                        'room_id' => $room->getUuid(),
                        'round_id' => $round->getId(),
                    ]),
                ])
            )
        );

        return new Response(status: 204);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/round/{round_id}/vote', name: 'round_vote', methods: ['POST'])]
    public function vote(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['round_id' => 'id'])] Round $round,
        VoteRepository $voteRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        HubInterface $hub,
    ): Response {
        if ($round->getStatus() !== RoundStatus::ACTIVE) {
            return new Response(status: 400);
        }

        $user = $this->getUser();
        $value = $request->request->get('value');

        if ($value === null) {
            return new Response(status: 400);
        }

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

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'member_list',
                    'event' => 'member_list:update',
                    'url' => $this->generateUrl('member_list_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'poker_table',
                    'event' => 'poker_table:update',
                    'url' => $this->generateUrl('poker_table_update', [
                        'room_id' => $room->getUuid(),
                        'round_id' => $round->getId(),
                    ]),
                ])
            )
        );

        return new Response(status: 204);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/round/{round_id}/reveal', name: 'round_reveal', methods: ['POST'])]
    public function reveal(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        #[MapEntity(mapping: ['round_id' => 'id'])] Round $round,
        TicketService $ticketService,
        RoundService $roundService,
        HubInterface $hub,
    ): Response {
        $this->denyAccessUnlessGranted(RoomVoter::REVEAL, $room);

        if ($round->getStatus() !== RoundStatus::ACTIVE) {
            return new Response(status: 400);
        }

        $round->setStatus(RoundStatus::REVEALED);

        $ticket->setStatus(TicketStatus::FINISHED);
        $ticket->setUpdatedAt();

        $ticketService->updateTicket($ticket);
        $roundService->updateRound($round);

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'member_list',
                    'event' => 'member_list:update',
                    'url' => $this->generateUrl('member_list_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'poker_table',
                    'event' => 'poker_table:update',
                    'url' => $this->generateUrl('poker_table_update', [
                        'room_id' => $room->getUuid(),
                        'round_id' => $round->getId(),
                    ]),
                ])
            )
        );

        $hub->publish(
            new Update(
                $room->getUuid(),
                json_encode([
                    'target' => 'ticket_list',
                    'event' => 'ticket_list:update',
                    'url' => $this->generateUrl('ticket_list_update', [
                        'room_id' => $room->getUuid(),
                    ]),
                ])
            )
        );

        return $this->render('room/stream/_poker_table.html.twig', [
            'room' => $room,
            'round' => $round,
            'votedUserIds' => null,
        ]);
    }
}
