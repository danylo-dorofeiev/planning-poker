<?php

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Modal
{
    public string $modal;
    public string $width="150";
    public string $title="";
    public bool $x=true;
    public string $reset="";
}
