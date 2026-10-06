<?php

namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IconExtension extends AbstractExtension
{
    public function __construct(
        private Environment $twig
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('icon', [$this, 'icon'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function icon(string $name, string $class = ''): string
    {
        return $this->twig->render('icons/' . $name . '.svg.twig', [
            'class' => $class,
        ]);
    }
}
