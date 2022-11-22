<?php

namespace App\Shared\CQRS;

interface CommandBus
{
    public function dispatch(Command $command): void;
}
