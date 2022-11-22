<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\Command;
use Ramsey\Uuid\UuidInterface;

class DeleteArticleCommand implements Command
{
    public function __construct(
        private readonly UuidInterface $id,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
}
