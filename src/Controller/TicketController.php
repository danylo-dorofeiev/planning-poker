<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Service\TicketService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    #[Route('/ticket/list', name: 'ticket_list')]
    public function list(TicketService $ticketService): Response {
        return $this->render('ticket/list.html.twig', [
            'tickets'=>$ticketService->findAll(),
        ]);
    }

    #[Route('/room/{uuid}/ticket/create', name: 'ticket_create', methods: ['POST'])]
    public function create(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, TicketService $ticketService): Response {
        $ticket = new Ticket();
        $ticket->setRoom($room);

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->createTicket($ticket);
        }

        return $this->redirectToRoute('room_show', [
            'uuid' => $room->getUuid(),
        ]);
    }

    #[Route('/room/{uuid}/ticket/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, Request $request, TicketService $ticketService): Response {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticketService->updateTicket($ticket);

            return $this->redirectToRoute('room_show', [
                'uuid' => $room->getUuid(),
            ]);
        }

        return $this->render('ticket/_edit_form.html.twig', [
            'room' => $room,
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/room/{uuid}/ticket/{id}/delete', name: 'ticket_delete', methods: ['POST'])]
    public function delete(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, TicketService $ticketService): Response {
        $ticketService->deleteTicket($ticket);

        return $this->redirectToRoute('room_show', [
            'uuid' => $room->getUuid(),
        ]);
    }
}
