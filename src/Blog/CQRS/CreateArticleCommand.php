<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\Command;
use Ramsey\Uuid\UuidInterface;

class CreateArticleCommand implements Command
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly string $title,
        private readonly string $body,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
}
