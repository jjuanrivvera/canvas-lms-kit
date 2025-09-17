<?php

declare(strict_types=1);

namespace CanvasLMS\Interfaces;

use CanvasLMS\Exceptions\CanvasApiException;

interface ApiInterface
{
    /**
     * Find a single record by ID
     *
     * @param int $id
     * @param array<string, mixed> $params Optional query parameters
     *
     * @throws CanvasApiException
     *
     * @return static
     */
    public static function find(int $id, array $params = []);

    /**
     * Get first page of records
     *
     * @param array<string, mixed> $params
     *
     * @throws CanvasApiException
     *
     * @return static[]
     */
    public static function get(array $params = []);

    /**
     * Get all records from all pages
     *
     * @param array<string, mixed> $params
     *
     * @throws CanvasApiException
     *
     * @return static[]
     */
    public static function all(array $params = []);
}
