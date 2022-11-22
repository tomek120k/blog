<?php

namespace App\Blog\CQRS;

use App\Repository\ArticleRepository;
use App\Shared\CQRS\CommandHandler;

/**
 * @todo co z usuwanie z systemu plików??
 */
class DeleteArticleFileHandler implements CommandHandler
{
    public function __construct(private readonly ArticleRepository $repository)
    {
    }

    public function __invoke(DeleteArticleFileCommand $command): void
    {
        $article = $this->repository->find($command->getId());
        if (!$article) {
            throw new \RuntimeException('Entity not found');
        }
        $article->setFile(null);
        $this->repository->save($article, true);
    }
}
