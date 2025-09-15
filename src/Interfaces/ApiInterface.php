<?php

namespace CanvasLMS\Interfaces;

use CanvasLMS\Exceptions\CanvasApiException;

interface ApiInterface
{
    /**
     * Find a single record by ID
     * @param int $id
     * @param array<string, mixed> $params Optional query parameters
     * @return static
     * @throws CanvasApiException
     */
    public static function find(int $id, array $params = []);

    /**
     * Get first page of records
     * @param array<string, mixed> $params
     * @return static[]
     * @throws CanvasApiException
     */
    public static function get(array $params = []);

    /**
     * Get all records from all pages
     * @param array<string, mixed> $params
     * @return static[]
     * @throws CanvasApiException
     */
    public static function all(array $params = []);
}
