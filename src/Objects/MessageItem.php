<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Message activity stream item
 */
class MessageItem extends ActivityStreamItem
{
    /**
     * @var int
     */
    public int $messageId;

    /**
     * @var string
     */
    public string $notificationCategory;
}
