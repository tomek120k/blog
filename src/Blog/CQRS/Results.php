<?php

namespace App\Blog\CQRS;

class Results
{
    public function __construct(
        private readonly iterable $results,
        private readonly int $currentPage,
        private readonly int $totalPages,
        private readonly int $totalResults,
        private readonly ?int $nextPage,
        private readonly ?int $prevPage,
    ) {
    }

    public function getResults(): iterable
    {
        return $this->results;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function getNextPage(): ?int
    {
        return $this->nextPage;
    }

    public function getPrevPage(): ?int
    {
        return $this->prevPage;
    }
}
