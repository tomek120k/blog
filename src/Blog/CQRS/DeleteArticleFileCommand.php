<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\Command;
use Ramsey\Uuid\UuidInterface;

class DeleteArticleFileCommand implements Command
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
