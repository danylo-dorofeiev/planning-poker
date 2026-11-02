<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private Environment $twig,
    ) {}

    public function create(
        string $template,
        array $parameters = [],
        int $status = 200,
    ): JsonResponse {
        return new JsonResponse([
            'notification' => $this->twig->render($template, $parameters),
        ], $status);
    }
}
