<?php

namespace CanvasLMS\Objects;

/**
 * Activity stream summary item
 */
class ActivityStreamSummary
{
    /**
     * @var string
     */
    public string $type;

    /**
     * @var int
     */
    public int $unreadCount;

    /**
     * @var int
     */
    public int $count;

    /**
     * Constructor
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            $property = lcfirst(str_replace('_', '', ucwords($key, '_')));
            if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * Convert to array
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
