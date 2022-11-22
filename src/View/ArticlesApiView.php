<?php

namespace App\View;

use App\Blog\CQRS\Results;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ArticlesApiView
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ArticleApiView $articleApiView
    ) {
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function normalize(Results $results): array
    {
        return [
            '_links' => [
                'self' => ['href' => $this->urlGenerator->generate('article_index', ['page' => $results->getCurrentPage()])],
                'first' => ['href' => $this->urlGenerator->generate('article_index')],
                'next' => ['href' => $results->getNextPage() ? $this->urlGenerator->generate('article_index', ['page' => $results->getNextPage()]) : null],
                'prev' => ['href' => $results->getPrevPage() ? $this->urlGenerator->generate('article_index', ['page' => $results->getPrevPage()]) : null],
            ],
            'count' => $results->getTotalResults(),
            'pages' => $results->getTotalPages(),
            '_embedded' => [
                'articles' => array_map(fn (array $article) => $this->articleApiView->normalize($article), $results->getResults()),
            ],
        ];
    }
}
