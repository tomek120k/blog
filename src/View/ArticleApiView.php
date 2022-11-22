<?php

namespace App\View;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticleApiView
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @param array{id: string, title: string, body: string, file: string} $article
     */
    public function normalize(array $article): array
    {
        return [
            'id' => $article['id'],
            'title' => $article['title'],
            'body' => $article['body'],
            'file' => $article['file'] ? '/uploads/article_imgs/'.$article['file'] : null,
            '_links' => [
                'self' => [
                    'href' => $this->urlGenerator->generate('article_read', ['article' => $article['id']]),
                ],
            ],
        ];
    }
}
