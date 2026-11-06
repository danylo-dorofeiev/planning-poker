<?php

namespace App\Controller;

use App\Entity\JiraConnection;
use App\Entity\Room;
use App\Exception\JiraConnectionExpiredException;
use App\Service\JiraImportService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class JiraImportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JiraImportService $jiraImportService,
        private NotificationService $notificationService,
    ) {
    }

    #[Route('/jira/connection', name: 'jira_connection', methods: ['GET'])]
    public function connection(): Response
    {
        return $this->render('services/jira/connection.html.twig', [
            'status' => $this->getUser()->getJiraConnection() ? 'connected' : 'disconnected',
        ]);
    }

    #[Route('/jira/connect', name: 'jira_connect')]
    public function connect(
        Request $request,
    ): Response {
        $state = bin2hex(random_bytes(32));

        $request->getSession()->set('jira_oauth_state', $state);

        return $this->redirect(
            $this->jiraImportService->getAuthorizationUrl($state)
        );
    }

    #[Route('/jira/callback', name: 'jira_callback')]
    public function callback(
        Request $request,
    ): Response {
        $state = $request->query->get('state');

        $session = $request->getSession();
        $storedState = $session->get('jira_oauth_state');

        $session->remove('jira_oauth_state');

        if (!$state || !$storedState || !hash_equals($storedState, $state)) {
            return $this->redirectToRoute('jira_connection', [
                'status' => 'connection_failed',
            ]);
        }

        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirectToRoute('jira_connection', [
                'status' => 'connection_failed'
            ]);
        }

        $tokens = $this->jiraImportService->exchangeCode($code);

        $resources = $this->jiraImportService->getAccessibleResources(
            $tokens['access_token']
        );

        if (!$resources) {
            return $this->redirectToRoute('jira_connection', [
                'status' => 'connection_failed',
            ]);
        }

        $jira = $resources[0];

        $jiraConnection = $this->getUser()->getJiraConnection();

        if (!$jiraConnection) {
            $jiraConnection = new JiraConnection();
            $jiraConnection->setUser($this->getUser());
            $this->entityManager->persist($jiraConnection);
        }

        $jiraConnection->setCloudId($jira['id']);
        $jiraConnection->setSiteUrl($jira['url']);
        $jiraConnection->setSiteName($jira['name']);
        $jiraConnection->setAccessToken($tokens['access_token']);
        $jiraConnection->setRefreshToken($tokens['refresh_token']);
        $jiraConnection->setCreatedAt(new \DateTimeImmutable);
        $jiraConnection->setAccessTokenExpiresAt(
            new \DateTimeImmutable(
                '+' . $tokens['expires_in'] . ' seconds'
            )
        );

        $this->entityManager->flush();

        return $this->redirectToRoute('jira_connection');
    }

    #[Route('room/{room_id}/jira/projects', name: 'jira_projects')]
    public function projects(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
    ): Response {
        $jiraConnection = $this->getUser()->getJiraConnection();

        if (!$jiraConnection) {
            return $this->render('services/jira/stream/_project_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        try {
            $accessToken = $this->jiraImportService
                ->getValidAccessToken($jiraConnection);
        } catch (JiraConnectionExpiredException $e) {
            return $this->render('services/jira/stream/_issue_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        $projects = $this->jiraImportService->getProjects(
            $accessToken,
            $jiraConnection->getCloudId(),
        );

        return $this->render('services/jira/stream/_project_list.html.twig', [
            'room' => $room,
            'projects' => $projects['values'],
            'connection' => true,
        ]);
    }

    #[Route('room/{room_id}/jira/project/{project}/issues', name: 'jira_issues')]
    public function issues(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        string $project,
    ): Response {
        $jiraConnection = $this->getUser()->getJiraConnection();

        if (!$jiraConnection) {
            return $this->render('services/jira/stream/_issue_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        try {
            $accessToken = $this->jiraImportService
                ->getValidAccessToken($jiraConnection);
        } catch (JiraConnectionExpiredException $e) {
            return $this->render('services/jira/stream/_issue_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        $result = $this->jiraImportService->searchIssues(
            $accessToken,
            $jiraConnection->getCloudId(),
            $project,
        );

        return $this->render('services/jira/stream/_issue_list.html.twig', [
            'room' => $room,
            'project' => $project,
            'issues' => $result['issues'] ?? [],
            'connection' => true,
        ]);
    }

    #[Route('/room/{room_id}/jira/project/{project}/import', name: 'jira_import', methods: ['POST'])]
    public function import(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        Request $request,
    ): Response {
        $jiraConnection = $this->getUser()->getJiraConnection();

        if (!$jiraConnection) {
            return $this->redirectToRoute('jira_connection');
        }

        $issueKeys = $request->request->all('issues');

        $this->jiraImportService->import(
            $this->getUser(),
            $room,
            $issueKeys,
        );

        return $this->redirectToRoute('room_show', [
            'room_id' => $room->getUuid()
        ]);
    }


    #[Route('/jira/disconnect', name: 'jira_disconnect', methods: ['POST'])]
    public function disconnect(
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'jira_disconnect',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $jiraConnection = $this->getUser()->getJiraConnection();

        if (!$jiraConnection) {
            return $this->redirectToRoute('jira_connection');
        }

        $refreshToken = $jiraConnection->getRefreshToken();

        if ($refreshToken) {
            try {
                $this->jiraImportService->revokeToken($refreshToken);
            } catch (\Throwable $e) {

            }
        }

        try {
            $this->entityManager->remove($jiraConnection);
            $this->entityManager->flush();

            $this->addFlash('success', 'Jira disconnected successfully.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Could not disconnect Jira.');
        }

        return $this->redirectToRoute('app_services');
    }
}
