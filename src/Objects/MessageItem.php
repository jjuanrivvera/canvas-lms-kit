<?php

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
