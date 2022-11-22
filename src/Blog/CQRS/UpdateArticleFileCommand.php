<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\Command;
use Ramsey\Uuid\UuidInterface;

class UpdateArticleFileCommand implements Command
{
    public function __construct(
        private readonly UuidInterface $id,
        private readonly string $filePath
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
}
