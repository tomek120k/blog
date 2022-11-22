<?php

namespace App\Blog\CQRS;

use App\Shared\CQRS\Query;

class ArticlesQuery implements Query
{
    public function __construct(private readonly int $limit = 5, private readonly int $page = 1)
    {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
