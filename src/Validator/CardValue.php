<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CardValue extends Constraint
{
    public string $message = 'The card value must contain up to 4 characters or one emoji.';
}
