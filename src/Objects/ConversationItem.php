<?php

declare(strict_types=1);

namespace CanvasLMS\Objects;

/**
 * Conversation activity stream item
 */
class ConversationItem extends ActivityStreamItem
{
    /**
     * @var int
     */
    public int $conversationId;

    /**
     * @var bool
     */
    public bool $private;

    /**
     * @var int
     */
    public int $participantCount;
}
