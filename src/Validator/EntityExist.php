<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class EntityExist extends Constraint
{
    public $message = 'Entity "%entity%" with property "%property%": "%value%" does not exist.';
    public $property = 'id';
    public $entity;

    public function __construct($entity = null, $property = null, $message = null, $options = null, array $groups = null, $payload = null)
    {
        parent::__construct($options);

        $this->entity = $entity ?? $this->entity;
        $this->property = $property ?? $this->property;
        $this->message = $message ?? $this->message;
    }
}
