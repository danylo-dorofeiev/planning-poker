<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\RoomRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TicketController extends AbstractController
{
    #[Route('/ticket/list', name: 'ticket_list')]
    public function list(TicketRepository $ticketRepository): Response {
        return $this->render('ticket/list.html.twig', [
            'tickets'=>$ticketRepository->findAll(),
        ]);
    }

    #[Route('/room/{uuid}/ticket/create', name: 'ticket_create', methods: ['POST'])]
    public function create(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Request $request, EntityManagerInterface $entityManager): Response {
        $ticket = new Ticket();

        $ticket->setRoom($room);

        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ticket);
            $entityManager->flush();
        }

        return $this->redirectToRoute('room_show', [
            'uuid' => $room->getUuid(),
        ]);
    }

    #[Route('/room/{uuid}/ticket/{id}/edit', name: 'ticket_edit', methods: ['GET', 'POST'])]
    public function edit(
        #[MapEntity(mapping: ['uuid' => 'uuid'])] Room $room, Ticket $ticket, Request $request, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
}
