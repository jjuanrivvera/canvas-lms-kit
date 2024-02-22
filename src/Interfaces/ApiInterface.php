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
     * Fetch all records
     * @param mixed[] $params
     * @return static[]
     * @throws CanvasApiException
     */
    public static function fetchAll(array $params = []);
}
