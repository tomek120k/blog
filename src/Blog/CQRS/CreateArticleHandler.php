<?php

namespace App\Blog\CQRS;

use App\AllowedTags;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Shared\CQRS\CommandHandler;

class CreateArticleHandler implements CommandHandler
{
    public function __construct(private readonly ArticleRepository $repository)
    {
    }

    public function __invoke(CreateArticleCommand $command): void
    {
        $article = new Article();
        $article->setId($command->getId());
        $article->setTitle(strip_tags($command->getTitle(), AllowedTags::TITLE_ALLOWED_TAGS));
        $article->setBody(strip_tags($command->getBody(), AllowedTags::BODY_ALLOWED_TAGS));
        $this->repository->save($article, true);
    }
}
