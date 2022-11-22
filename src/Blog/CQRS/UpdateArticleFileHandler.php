<?php

namespace App\Blog\CQRS;

use App\Repository\ArticleRepository;
use App\Shared\CQRS\CommandHandler;

class UpdateArticleFileHandler implements CommandHandler
{
    public function __construct(private readonly ArticleRepository $repository)
    {
    }

    public function __invoke(UpdateArticleFileCommand $command): void
    {
        $article = $this->repository->find($command->getId());
        if (!$article) {
            throw new \RuntimeException('Entity not found');
        }
        $article->setFile($command->getFilePath());
        $this->repository->save($article, true);
    }
}
