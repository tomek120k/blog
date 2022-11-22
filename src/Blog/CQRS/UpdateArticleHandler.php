<?php

namespace App\Blog\CQRS;

use App\AllowedTags;
use App\Repository\ArticleRepository;
use App\Shared\CQRS\CommandHandler;

class UpdateArticleHandler implements CommandHandler
{
    public function __construct(private readonly ArticleRepository $repository)
    {
    }

    public function __invoke(UpdateArticleCommand $command): void
    {
        $article = $this->repository->find($command->getId());
        if (!$article) {
            throw new \RuntimeException('Entity not found');
        }
        $article->setTitle(strip_tags($command->getTitle(), AllowedTags::TITLE_ALLOWED_TAGS));
        $article->setBody(strip_tags($command->getBody(), AllowedTags::BODY_ALLOWED_TAGS));
        $this->repository->save($article, true);
    }
}
