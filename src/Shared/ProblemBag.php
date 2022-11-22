<?php

namespace App\Shared;

class ProblemBag
{
    public const TYPE_VALIDATION_ERROR = 'validation_error';
    public const TYPE_INVALID_REQUEST_BODY_FORMAT = 'invalid_body_format';
    public const TYPE_INVALID_FILE = 'invalid_file';
    public const TYPE_NOT_FOUND = 'not_found';

    private static array $titles = [
        self::TYPE_VALIDATION_ERROR => 'There was a validation error',
        self::TYPE_INVALID_REQUEST_BODY_FORMAT => 'Invalid JSON format sent',
        self::TYPE_NOT_FOUND => 'Resource not found',
    ];

    private readonly string $title;

    private array $data = [];

    public function __construct(
        private readonly int $statusCode,
        private readonly string $type,
    ) {
        if (!isset(self::$titles[$type])) {
            throw new \InvalidArgumentException('No title for type '.$type);
        }
        $this->title = self::$titles[$type];
    }

    public function set($name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->data,
            [
                'status' => $this->statusCode,
                'type' => $this->type,
                'title' => $this->title,
            ]
        );
    }
}
