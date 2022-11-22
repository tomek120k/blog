<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\QueryHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Doctrine\DBAL\SingleTableQueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;

class ArticlesQueryHandler implements QueryHandler
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(ArticlesQuery $limit): Results
    {
        try {
            $queryBuilder = new QueryBuilder($this->connection);
            $queryBuilder->select('a.*')->from('article', 'a');
            $countField = 'a.id';
            $adapter = new SingleTableQueryAdapter($queryBuilder, $countField);

            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($limit->getLimit());

            $pagerfanta->setCurrentPage($limit->getPage());

            return new Results(
                $pagerfanta->getCurrentPageResults(),
                $pagerfanta->getCurrentPage(),
                $pagerfanta->getNbPages(),
                $pagerfanta->count(),
                $pagerfanta->hasNextPage() ? $pagerfanta->getNextPage() : null,
                $pagerfanta->hasPreviousPage() ? $pagerfanta->getPreviousPage() : null,
            );
        } catch (OutOfRangeCurrentPageException $e) {
            throw new \RuntimeException('Page Not found');
        }
    }
}
