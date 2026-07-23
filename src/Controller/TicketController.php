<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Event\TicketCreatedEvent;
use App\Event\TicketDeletedEvent;
use App\Event\TicketEditedEvent;
use App\Form\TicketType;
use App\Service\RoomService;
use App\Service\TicketService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[IsGranted('ROLE_USER')]
class TicketController extends AbstractController
{
    #[Route('/ticket/list', name: 'ticket_list', methods: ['GET'])]
    public function list(TicketService $ticketService, Security $security): Response {
        return $this->render('ticket/list.html.twig', [
            'tickets'=>$ticketService->findAllByOwner($security->getUser()),
        ]);
    }

    #[Route('/room/{uuid}/ticket/create', name: 'ticket_create', methods: ['POST'])]
    public function create(#[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, RoomService $roomService, TicketService $ticketService, EventDispatcherInterface $eventDispatcher): Response {
        $ticket = new Ticket();
        $ticket->setRoom($room);

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->createTicket($ticket);

            $room->setUpdatedAt();
            $roomService->updateRoom($room);

            $eventDispatcher->dispatch(
                new TicketCreatedEvent($ticket),
            );

            $ticket = new Ticket();
            $ticket->setRoom($room);

            return $this->render('ticket/form/create_form.html.twig', [
                'form' => $this->createForm(TicketType::class, $ticket)->createView(),
                'room' => $room,
            ]);
        }

        return new Response('', 302);
    }

    #[Route('/room/{uuid}/ticket/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, RoomService $roomService, Ticket $ticket, TicketService $ticketService, EventDispatcherInterface $eventDispatcher,): Response {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->updateTicket($ticket);

            $room->setUpdatedAt();
            $roomService->updateRoom($room);

            $eventDispatcher->dispatch(
                new TicketEditedEvent($ticket),
            );

            return $this->redirectToRoute('room_show', [
                'uuid' => $room->getUuid(),
            ]);
        }

        return $this->render('ticket/form/edit_form.html.twig', [
            'room' => $room,
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/room/{uuid}/ticket/{id}/delete', name: 'ticket_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, RoomService $roomService, Ticket $ticket, TicketService $ticketService, EventDispatcherInterface $eventDispatcher): Response {
        $eventDispatcher->dispatch(
            new TicketDeletedEvent($ticket),
        );

        $ticketService->deleteTicket($ticket);

        $room->setUpdatedAt();
        $roomService->updateRoom($room);

        return $this->redirectToRoute('room_show', [
            'uuid' => $room->getUuid(),
        ]);
    }
}
