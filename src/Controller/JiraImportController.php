<?php

namespace App\Controller;

use App\Entity\JiraConnection;
use App\Entity\Room;
use App\Repository\JiraConnectionRepository;
use App\Service\JiraImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class JiraImportController extends AbstractController
{
    public function __construct(
        private JiraImportService $jiraImportService,
        private JiraConnectionRepository $jiraConnectionRepository,
    ) {
    }

    public function isJiraConnected(): bool {
        $jiraConnection = $this->jiraConnectionRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$jiraConnection) {
            return false;
        }

        return true;
    }

    public function isJiraConnectionActive(): bool {
        $jiraConnection = $this->jiraConnectionRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$jiraConnection) {
            return false;
        }

        return $jiraConnection->getAccessTokenExpiresAt()
            > new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    #[Route('/jira/connection', name: 'jira_connection', methods: ['GET'])]
    public function connection(): Response {
        $isJiraConnect = $this->isJiraConnected();
        $isJiraConnectionActive = $this->isJiraConnectionActive();

        if ($isJiraConnect and $isJiraConnectionActive) {
            return $this->redirectToRoute('jira_status', [
                'status' => 'connected'
            ]);
        }

        return $this->render('services/jira/connection.html.twig');
    }

    #[Route('/jira/connection/status', name: 'jira_status', methods: ['GET'])]
    public function status(
        Request $request,
    ): Response {
        $isJiraConnect = $this->isJiraConnected();
        $isJiraConnectionActive = $this->isJiraConnectionActive();

        $status = $request->get('status');

        if (!$status) {
            if ($isJiraConnect and $isJiraConnectionActive) {
                return $this->render('services/jira/status.html.twig', [
                    'status' => 'connected'
                ]);
            }

            return $this->render('services/jira/status.html.twig', [
                'status' => 'disconnected'
            ]);
        }
        elseif ($status == 'connected') {
            return $this->render('services/jira/status.html.twig', [
                'status' => 'connected'
            ]);
        }
        elseif ($status == 'connection_error') {
            return $this->render('services/jira/status.html.twig', [
                'status' => 'connection_error'
            ]);
        }
        elseif ($status == 'disconnected') {
            return $this->render('services/jira/status.html.twig', [
                'status' => 'disconnected'
            ]);
        }
        else {
            throw new NotFoundHttpException();
        }
    }

    #[Route('/jira/connect', name: 'jira_connect')]
    public function connect(
        JiraImportService $jiraImportService,
        Request $request,
    ): Response {
        $state = bin2hex(random_bytes(32));

        $request->getSession()->set('jira_oauth_state', $state);

        return $this->redirect(
            $jiraImportService->getAuthorizationUrl($state)
        );
    }

    #[Route('/jira/callback', name: 'jira_callback')]
    public function callback(
        Request $request,
        JiraImportService $jiraImportService,
        EntityManagerInterface $entityManager,
    ): Response {
        $state = $request->query->get('state');

        if (!$state || !hash_equals(
                $request->getSession()->get('jira_oauth_state', ''),
                $state
            )) {
            return $this->redirectToRoute('jira_status', [
                'status' => 'connection_error'
            ]);
        }

        $request->getSession()->remove('jira_oauth_state');

        $code = $request->query->get('code');

        if (!$code) {
            return $this->redirectToRoute('jira_status', [
                'status' => 'connection_error'
            ]);
        }

        $tokens = $jiraImportService->exchangeCode($code);

        $resources = $jiraImportService->getAccessibleResources(
            $tokens['access_token']
        );

        if (!$resources) {
            throw new \RuntimeException('No Jira sites available.');
        }

        $jira = $resources[0];

        $isJiraConnected = $this->isJiraConnected();

        if (!$isJiraConnected) {
            $jiraConnection = new JiraConnection();
            $jiraConnection->setUser($this->getUser());
            $entityManager->persist($jiraConnection);
        } else {
            $jiraConnection = $this->getUser()->getJiraConnection();
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

        $entityManager->flush();

        return $this->redirectToRoute('jira_status', [
            'status' => 'connected'
        ]);
    }

    #[Route('room/{room_id}/jira/projects', name: 'jira_projects')]
    public function projects(
        #[MapEntity(mapping: ['room_id' => 'uuid'])] Room $room,
        JiraImportService $jiraImportService,
    ): Response {
        $isJiraConnected = $this->isJiraConnected();
        $isJiraConnectionActive = $this->isJiraConnectionActive();

        if (!$isJiraConnected || !$isJiraConnectionActive) {
            return $this->render('services/jira/stream/_project_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        $jiraConnection = $this->getUser()->getJiraConnection();

        $projects = $jiraImportService->getProjects(
            $jiraConnection->getAccessToken(),
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
        JiraImportService $jiraImportService,
    ): Response {
        $isJiraConnected = $this->isJiraConnected();
        $isJiraConnectionActive = $this->isJiraConnectionActive();

        if (!$isJiraConnected || !$isJiraConnectionActive) {
            return $this->render('services/jira/stream/_project_list.html.twig', [
                'room' => $room,
                'connection' => false,
            ]);
        }

        $jiraConnection = $this->getUser()->getJiraConnection();

        $result = $jiraImportService->searchIssues(
            $jiraConnection->getAccessToken(),
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
        $isJiraConnected = $this->isJiraConnected();
        $isJiraConnectionActive = $this->isJiraConnectionActive();

        if (!$isJiraConnected || !$isJiraConnectionActive) {
            return $this->redirectToRoute('jira_connect', [
                'room_id' => $room->getUuid(),
            ]);
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
        JiraImportService $jiraImportService,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'jira_disconnect',
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException();
        }

        $connection = $this->jiraConnectionRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        if (!$connection) {
            return $this->redirectToRoute('jira_connection');
        }

        $refreshToken = $connection->getRefreshToken();

        if ($refreshToken) {
            try {
                $jiraImportService->revokeToken($refreshToken);
            } catch (\Throwable $e) {

            }
        }

        try {
            $entityManager->remove($connection);
            $entityManager->flush();

            $this->addFlash('success', 'Jira disconnected successfully.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Could not disconnect Jira.');
        }

        return $this->redirectToRoute('app_services');
    }
}
