<?php

declare(strict_types=1);

namespace CanvasLMS\Interfaces;

interface DTOInterface
{
    /**
     * DTO to API array
     *
     * @return mixed[]
     */
    public function toApiArray(): array;
}
