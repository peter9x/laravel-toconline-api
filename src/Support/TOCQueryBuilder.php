<?php

declare(strict_types=1);

namespace Mupy\TOConline\Support;

use BackedEnum;
use Mupy\TOConline\TOCClient;

final class TOCQueryBuilder
{
    private array $filters = [];

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

    /** Add a filter field */
    public function where(string $field, string|int|float|bool|BackedEnum|null $value): self
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }
        $this->filters[$field] = $value;

        return $this;
    }

    /** Example of a typed helper */
    public function whereStatus(BackedEnum|int $status): self
    {
        if ($status instanceof BackedEnum) {
            $status = $status->value;
        }
        $this->filters['status'] = $status;

        return $this;
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

    /** Build query params array */
    private function toQuery(): array
    {
        $query = [];

        foreach ($this->filters as $key => $value) {
            $query["filter[{$key}]"] = $value;
        }

        if (! empty($this->sort)) {
            $query['sort'] = implode(',', $this->sort);
        }

        if (! empty($this->includes)) {
            $query['include'] = implode(',', array_unique($this->includes));
        }

        if ($this->page !== null || $this->pageSize !== null) {
            if ($this->page !== null) {
                $query['page[number]'] = $this->page;
            }
            if ($this->pageSize !== null) {
                $query['page[size]'] = $this->pageSize;
            }
        }

        return $query;
    }

    public function find(int|string $id): array
    {
        $uri = rtrim($this->endpoint, '/').'/'.urlencode((string) $id);

        return $this->client->request('GET', $uri);
    }

    /** Execute GET request */
    public function get(): array
    {
        $queryString = http_build_query($this->toQuery());
        $uri = "{$this->endpoint}?".$queryString;

        return $this->client->request('GET', $uri);
    }
}
