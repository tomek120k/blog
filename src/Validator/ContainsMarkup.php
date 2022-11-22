<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ContainsMarkup extends Constraint
{
    public string $message = 'The string "{{ string }}" contains an illegal markups: it can only contain allowed tags {{ allowedTags }}';

    public string $allowedTags = '';
}
