<?php

namespace App\Service;

use App\Entity\JiraConnection;
use App\Entity\Room;
use App\Entity\Ticket;
use App\Entity\User;
use App\Enum\TicketSource;
use App\Exception\JiraConnectionExpiredException;
use App\Repository\JiraConnectionRepository;
use App\Twig\AdfExtension;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JiraImportService
{
    private const AUTHORIZE_URL = 'https://auth.atlassian.com/authorize';
    private const TOKEN_URL = 'https://auth.atlassian.com/oauth/token';
    private const RESOURCES_URL = 'https://api.atlassian.com/oauth/token/accessible-resources';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
        private string $clientSecret,
        private string $redirectUri,
        private EntityManagerInterface $entityManager,
        private AdfExtension $adfExtension,
        private JiraConnectionRepository $jiraConnectionRepository,
    ) {
    }

    public function import(
        User $user,
        Room $room,
        array $issueKeys,
    ): void {
        $connection = $user->getJiraConnection();

        if (!$connection) {
            throw new \RuntimeException('Jira connection not found.');
        }

        try {
            $accessToken = $this
                ->getValidAccessToken($connection);
        } catch (JiraConnectionExpiredException $e) {

        }

        foreach ($issueKeys as $issueKey) {
            $issue = $this->getIssue(
                $this->getValidAccessToken($connection),
                $connection->getCloudId(),
                $issueKey,
            );

            $ticket = new Ticket();

            $ticket->setRoom($room);
            $ticket->setCreatedAt(new \DateTimeImmutable($issue['fields']['created']));
            $ticket->setTitle($issue['fields']['summary']);

            $ticket->setDescription(
                $this->adfExtension->convertAdfToMarkdown(
                    $issue['fields']['description'] ?? null
                )
            );

            $ticket->setKey($issue['key']);

            $ticket->setSource(TicketSource::JIRA);

            $ticket->setIsTimeBased(true);

            $this->entityManager->persist($ticket);
        }

        $this->entityManager->flush();
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTHORIZE_URL . '?' . http_build_query([
                'audience' => 'api.atlassian.com',
                'client_id' => $this->clientId,
                'scope' => 'read:jira-work write:jira-work read:jira-user offline_access',
                'redirect_uri' => $this->redirectUri,
                'state' => $state,
                'response_type' => 'code',
                'prompt' => 'consent',
            ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = $this->httpClient->request('POST', self::TOKEN_URL, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ],
        ]);

        return $response->toArray();
    }

    public function getAccessibleResources(string $accessToken): array
    {
        return $this->httpClient
            ->request('GET', self::RESOURCES_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ])
            ->toArray();
    }

    public function getProjects(
        string $accessToken,
        string $cloudId,
        string $search = '',
    ): array {
        $startAt = 0;
        $maxResults = 50;
        $allProjects = [];

        do {
            $response = $this->request(
                'GET',
                $cloudId,
                $accessToken,
                '/rest/api/3/project/search?' . http_build_query([
                    'startAt' => $startAt,
                    'maxResults' => $maxResults,
                ])
            );

            $projects = $response['values'] ?? [];
            $allProjects = array_merge($allProjects, $projects);

            $startAt += count($projects);
            $total = $response['total'] ?? count($allProjects);
        } while (count($projects) > 0 && $startAt < $total);

        if ($search !== '') {
            $term = mb_strtolower($search);

            $allProjects = array_filter(
                $allProjects,
                static fn (array $project): bool =>
                    str_contains(mb_strtolower($project['name'] ?? ''), $term)
                    || str_contains(mb_strtolower($project['key'] ?? ''), $term)
            );
        }

        return ['values' => array_values($allProjects)];
    }

    private function request(
        string $method,
        string $cloudId,
        string $accessToken,
        string $endpoint,
        array $options = [],
    ): array {
        $options['headers']['Authorization'] = 'Bearer ' . $accessToken;
        $options['headers']['Accept'] = 'application/json';

        return $this->httpClient
            ->request(
                $method,
                sprintf(
                    'https://api.atlassian.com/ex/jira/%s/%s',
                    $cloudId,
                    ltrim($endpoint, '/')
                ),
                $options
            )
            ->toArray();
    }

    public function searchIssues(
        string $accessToken,
        string $cloudId,
        string $projectKey,
    ): array {
        return $this->request(
            'GET',
            $cloudId,
            $accessToken,
            '/rest/api/3/search/jql',
            [
                'query' => [
                    'jql' => sprintf(
                        'project = "%s" ORDER BY updated DESC',
                        $projectKey
                    ),
                    'maxResults' => 500,
                    'fields' => 'summary,description,status,created,issuetype',
                ],
            ]
        );
    }

    public function getIssue(
        string $accessToken,
        string $cloudId,
        string $issueKey,
    ): array {
        return $this->request(
            'GET',
            $cloudId,
            $accessToken,
            '/rest/api/3/issue/' . urlencode($issueKey),
            [
                'query' => [
                    'fields' => 'summary,description,status,created,issuetype',
                ],
            ]
        );
    }

    public function revokeToken(string $refreshToken): void
    {
        $response = $this->httpClient->request(
            'POST',
            'https://auth.atlassian.com/oauth/revoke',
            [
                'json' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'token' => $refreshToken,
                ],
            ]
        );

        $response->getStatusCode();
    }

    public function updateOriginalEstimate(
        string $accessToken,
        string $cloudId,
        string $issueKey,
        float $votingResult,
    ): void {
        $response = $this->httpClient->request('PUT', sprintf(
            'https://api.atlassian.com/ex/jira/%s/rest/api/3/issue/%s',
            $cloudId,
            $issueKey,
        ), [
            'auth_bearer' => $accessToken,
            'json' => [
                'fields' => [
                    'timetracking' => [
                        'originalEstimate' => $votingResult . 'h',
                    ],
                ],
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf(
                'Jira API error (%d): %s',
                $statusCode,
                $response->getContent(false),
            ));
        }
    }

    public function getValidAccessToken(JiraConnection $connection): string
    {
        $createdAt = $connection->getCreatedAt();

        if (!$createdAt || $createdAt <= new \DateTimeImmutable('-365 days')) {
            $this->expireConnection($connection);
        }

        $expiresAt = $connection->getAccessTokenExpiresAt();

        if (!$expiresAt || $expiresAt <= new \DateTimeImmutable('+5 minutes')) {
            $refreshToken = $connection->getRefreshToken();

            if (!$refreshToken) {
                $this->expireConnection($connection);
            }

            try {
                $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                    'json' => [
                        'grant_type' => 'refresh_token',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'refresh_token' => $refreshToken,
                    ],
                ]);

                $data = $response->toArray();

            } catch (HttpExceptionInterface $e) {
                $content = $e->getResponse()->getContent(false);
                $error = json_decode($content, true);

                if (($error['error'] ?? null) === 'invalid_grant') {
                    $this->expireConnection($connection);
                }

                throw $e;
            }

            $connection->setAccessToken($data['access_token']);

            if (!empty($data['refresh_token'])) {
                $connection->setRefreshToken($data['refresh_token']);
            }

            $connection->setAccessTokenExpiresAt(
                new \DateTimeImmutable('+' . $data['expires_in'] . ' seconds')
            );

            $this->entityManager->flush();
        }

        return $connection->getAccessToken();
    }

    private function expireConnection(JiraConnection $connection): never
    {
        $this->entityManager->remove($connection);
        $this->entityManager->flush();

        throw new JiraConnectionExpiredException(
            'Jira connection has expired.'
        );
    }
}
