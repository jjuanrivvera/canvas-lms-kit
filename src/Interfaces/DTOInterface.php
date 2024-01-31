<?php

namespace CanvasLMS\Interfaces;

interface DTOInterface
{
    /**
     * DTO to API array
     * @return mixed[]
     */
    public function toApiArray(): array;
}
