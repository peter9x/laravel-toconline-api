<?php 

declare(strict_types=1);

namespace Mupy\TOConline\DTO;

final class SalesDocument
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $series,
        public readonly string $number,
        public readonly string $status,
        public readonly array $rawData = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['series'] ?? '',
            $data['number'] ?? '',
            $data['status'] ?? '',
            $data
        );
    }
}
