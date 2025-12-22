<?php

declare(strict_types=1);

namespace Mupy\TOConline\Support;

use BackedEnum;
use Mupy\TOConline\TOCClient;

final class TOCQueryBuilder
{
    private array $filters = [];

    private array $rawFilters = [];

    private array $sort = [];

    private array $includes = [];

    private ?int $page = null;

    private ?int $pageSize = null;

    public function __construct(
        private readonly TOCClient $client,
        private readonly string $endpoint
    ) {}

    public static function make(TOCClient $client, string $endpoint): self
    {
        return new self($client, $endpoint);
    }

    /** Simple field filters */
    public function where(string $field, string|int|float|bool|BackedEnum|null $value): self
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        $this->filters[$field] = $value;

        return $this;
    }

    /** RAW filter (Postman style) */
    private function addRaw(string $expression): self
    {
        // TOConline requires the whole filter expression wrapped in quotes
        $this->rawFilters[] = '"'.$expression.'"';

        return $this;
    }

    /** Filter by updated_at with operator */
    public function whereUpdatedAt(string $operator, string $dateTime): self
    {
        return $this->addRaw("documents.updated_at{$operator}'{$dateTime}'::TIMESTAMP");
    }

    /** Filter by created_at */
    public function whereCreatedAt(string $operator, string $dateTime): self
    {
        return $this->addRaw("documents.created_at{$operator}'{$dateTime}'::TIMESTAMP");
    }

    /** Filter by document date */
    public function whereDatedAt(string $operator, string $dateTime): self
    {
        return $this->addRaw("documents.date{$operator}'{$dateTime}'::TIMESTAMP");
    }

    /** Sorting */
    public function orderBy(string $field, string $direction = 'asc'): self
    {
        $prefix = strtolower($direction) === 'desc' ? '-' : '';
        $this->sort[] = $prefix.$field;

        return $this;
    }

    /** Relationship includes */
    public function include(array|string $relations): self
    {
        $relations = is_array($relations) ? $relations : [$relations];
        $this->includes = array_merge($this->includes, $relations);

        return $this;
    }

    /** Pagination */
    public function paginate(int $page = 1, int $pageSize = 50): array
    {
        $this->page = $page;
        $this->pageSize = $pageSize;

        return $this->get();
    }

    /** Build the RAW query string manually (Postman-style) */
    private function buildQueryString(): string
    {
        $queryParams = [];
        // Raw filter override (TOConline uses `filter=...`)
        if (! empty($this->rawFilters)) {
            $queryParams['filter'] = implode('&', $this->rawFilters);
        }

        // Normal filter fields: filter[field]=value
        foreach ($this->filters as $field => $value) {
            $queryParams["filter[{$field}]="] = $value;
        }

        if (! empty($this->sort)) {
            $queryParams['sort'] = implode(',', $this->sort);
        }

        if (! empty($this->includes)) {
            $queryParams['include'] = implode(',', array_unique($this->includes));
        }

        if ($this->page !== null) {
            $queryParams['page[number]'] = $this->page;
        }

        if ($this->pageSize !== null) {
            $queryParams['page[size]'] = $this->pageSize;
        }

        return http_build_query($queryParams);
    }

    /** Execute GET request */
    public function get(): array
    {
        $query = $this->buildQueryString();
        $uri = $this->endpoint.'?'.$query;

        return $this->client->request('GET', $uri);
    }

    public function find(int|string $id): array
    {
        $uri = rtrim($this->endpoint, '/').'/'.urlencode((string) $id);

        return $this->client->request('GET', $uri);
    }
}
