<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Enum\TicketStatus;
use App\Event\PokerTableEvent;
use App\Event\TicketCardListEvent;
use App\Form\TicketType;
use App\Repository\RoundRepository;
use App\Repository\TicketRepository;
use App\Service\RoomService;
use App\Service\RoundService;
use App\Service\TicketService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[IsGranted('ROLE_USER')]
class TicketController extends AbstractController
{
    #[Route('/room/{room_id}/ticket/create', name: 'ticket_create', methods: ['POST'])]
    public function create(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        Request $request,
        RoomService $roomService,
        TicketService $ticketService,
        HubInterface $hub,
    ): Response {
        $ticket = new Ticket();
        $ticket->setRoom($room);

        $ticketCreateForm = $this->createForm(TicketType::class, $ticket);
        $ticketCreateForm->handleRequest($request);

        if ($ticketCreateForm->isSubmitted() && $ticketCreateForm->isValid()) {
            $ticketService->createTicket($ticket);

            $room->setUpdatedAt();
            $roomService->updateRoom($room);

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

            $ticket = new Ticket();
            $ticket->setRoom($room);

            return $this->render('ticket/form/_create_form.html.twig', [
                '$ticketCreateForm' => $this->createForm(TicketType::class, $ticket)->createView(),
                'room' => $room,
            ]);
        }
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/edit', name: 'ticket_edit', methods: ['POST'])]
    public function edit(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        RoomService $roomService,
        TicketService $ticketService,
        Request $request,
        HubInterface $hub,
    ): Response {
        if ($ticket->getStatus() == TicketStatus::VOTING) {
            return new Response(status: 400);
        }

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->updateTicket($ticket);

            $room->setUpdatedAt();
            $roomService->updateRoom($room);

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

            return $this->redirectToRoute('room_show', [
                'room_id' => $room->getUuid(),
            ]);
        }
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/delete', name: 'ticket_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        RoomService $roomService,
        TicketService $ticketService,
        HubInterface $hub,
    ): Response {
        if ($ticket->getStatus() == TicketStatus::VOTING) {
            return new Response(status: 400);
        }

        $ticketService->deleteTicket($ticket);

        $room->setUpdatedAt();
        $roomService->updateRoom($room);

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

        return $this->redirectToRoute('room_show', [
            'room_id' => $room->getUuid(),
        ]);
    }
}
