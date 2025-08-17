<?php

namespace CanvasLMS\Interfaces;

use CanvasLMS\Exceptions\CanvasApiException;

interface ApiInterface
{
    /**
     * Find a single record by ID
     * @param int $id
     * @return static
     * @throws CanvasApiException
     */
    public static function find(int $id);

    /**
     * Get first page of records
     * @param mixed[] $params
     * @return static[]
     * @throws CanvasApiException
     */
    public static function get(array $params = []);

    /**
     * Get all records from all pages
     * @param mixed[] $params
     * @return static[]
     * @throws CanvasApiException
     */
    public static function all(array $params = []);
}
