<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Ticket;
use App\Enum\TicketSource;
use App\Enum\TicketStatus;
use App\Form\TicketType;
use App\Service\JiraImportService;
use App\Service\NotificationService;
use App\Service\RoomService;
use App\Service\TicketService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

        $ticketCreateForm = $this->createForm(TicketType::class, $ticket, [
            'editable' => $ticket->getSource() !== TicketSource::JIRA,
        ]);
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

        if ($ticket->getSource() == TicketSource::JIRA) {
            return new Response(status: 400);
        }

        $form = $this->createForm(TicketType::class, $ticket, [
            'editable' => $ticket->getSource() !== TicketSource::JIRA,
        ]);
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

    #[Route('/room/{room_id}/ticket/{ticket_id}/voting_result', name: 'ticket_voting_result', methods: ['POST'])]
    public function voting_result (
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        TicketService $ticketService,
        Request $request,
        HubInterface $hub,
        JiraImportService $jiraImportService,
        NotificationService $notificationService,
    ): Response {
        $user = $this->getUser();

        if ($room->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($ticket->getRoom() !== $room) {
            throw $this->createNotFoundException();
        }

        $payload = $request->getPayload();

        $result = $payload->get('voting-result');

        if (!is_string($result) || trim($result) === '') {
            throw $this->createAccessDeniedException();
        }

        $result = trim($result);

        if ($ticket->isTimeBased()) {
            if (!preg_match('/^\d+$/', $result)) {
                throw $this->createAccessDeniedException();
            }
        }

        /*==> JIRA ===*/
        if ($ticket->getSource() == TicketSource::JIRA) {
            $connection = $jiraImportService
                ->getActiveConnection($user);

            if (!$connection) {
                return $notificationService->create(
                    'notifications/jira/jira_expired.html.twig',
                    status: 401,
                );
            }

            $jiraImportService->updateOriginalEstimate(
                $connection->getAccessToken(),
                $connection->getCloudId(),
                $ticket->getKey(),
                $result,
            );
        }
        /*==< JIRA ===*/

        $ticket->setVotingResult($result);

        $ticket->setStatus(TicketStatus::FINISHED);

        $ticketService->updateTicket($ticket);

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

        return $this->json([
            'success' => true,
            'votingResult' => $ticket->getVotingResult(),
        ]);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/voting_result_delete', name: 'ticket_voting_result_delete', methods: ['POST'])]
    public function voting_result_delete (
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        TicketService $ticketService,
        HubInterface $hub,
        JiraImportService $jiraImportService,
        NotificationService $notificationService,
    ): Response {
        if ($room->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($ticket->getRoom() !== $room) {
            throw $this->createNotFoundException();
        }

        /*==> JIRA ===*/
        if ($ticket->getSource() == TicketSource::JIRA) {
            $user = $this->getUser();

            $connection = $jiraImportService
                ->getActiveConnection($user);

            if (!$connection) {
                return $notificationService->create(
                    'notifications/jira/jira_expired.html.twig',
                    status: 401,
                );
            }

            $jiraImportService->updateOriginalEstimate(
                $connection->getAccessToken(),
                $connection->getCloudId(),
                $ticket->getKey(),
                0,
            );
        }
        /*==< JIRA ===*/

        $ticket->setVotingResult(null);

        if ($ticket->getStatus() !== TicketStatus::VOTING) {
            if ($ticket->getRounds() !== null) {
                $ticket->setStatus(TicketStatus::REVEALED);
            } else {
                $ticket->setStatus(TicketStatus::PENDING);
            }
        }

        $ticketService->updateTicket($ticket);

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

        return $this->json([
            'success' => true,
        ]);
    }

    #[Route('/room/{room_id}/ticket/{ticket_id}/time-based', name: 'ticket_time_based', methods: ['POST'])]
    public function time_based(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        #[MapEntity(mapping: ['ticket_id' => 'uuid'])] Ticket $ticket,
        Request $request,
        TicketService $ticketService,
        HubInterface $hub,
        NotificationService $notificationService,
    ): JsonResponse {
        if ($room->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($ticket->getRoom() !== $room) {
            throw $this->createNotFoundException();
        }

        if ($ticket->getSource() == TicketSource::JIRA) {
            return $notificationService->create(
                'notifications/jira/is_time_based.html.twig',
                status: 401,
            );
        }

        $value = $request->getPayload()->get('isTimeBased');

        if (!in_array($value, ['0', '1'], true)) {
            return $this->json(['error' => 'Invalid value.'], 400);
        }

        $ticket->setIsTimeBased($value === '1');

        if ($ticket->getVotingResult() !== null) {
            $ticket->setVotingResult(null);

            $ticketStatus = $ticket->getStatus();

            if ($ticketStatus == TicketStatus::FINISHED) {
                if ($ticketStatus !== TicketStatus::VOTING) {
                    if ($ticket->getRounds() !== null) {
                        $ticket->setStatus(TicketStatus::REVEALED);
                    } else {
                        $ticket->setStatus(TicketStatus::PENDING);
                    }
                }
            }

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
        }

        $ticketService->updateTicket($ticket);

        return $this->json([
            'success' => true,
            'isTimeBased' => $ticket->isTimeBased(),
        ]);
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
