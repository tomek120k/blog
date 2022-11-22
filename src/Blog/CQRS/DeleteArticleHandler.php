<?php

namespace App\Blog\CQRS;

use App\Repository\ArticleRepository;
use App\Shared\CQRS\CommandHandler;

class DeleteArticleHandler implements CommandHandler
{
    public function __construct(private readonly ArticleRepository $repository)
    {
    }

    public function __invoke(DeleteArticleCommand $command): void
    {
        $article = $this->repository->find($command->getId());
        if (!$article) {
            throw new \RuntimeException('Entity not found');
        }

        $this->repository->remove($article, true);
    }
}
