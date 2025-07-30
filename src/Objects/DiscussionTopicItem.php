<?php

namespace CanvasLMS\Objects;

/**
 * Discussion topic activity stream item
 */
class DiscussionTopicItem extends ActivityStreamItem
{
    /**
     * @var int
     */
    public int $discussionTopicId;

    /**
     * @var int
     */
    public int $totalRootDiscussionEntries;

    /**
     * @var bool
     */
    public bool $requireInitialPost;

    /**
     * @var bool|null
     */
    public ?bool $userHasPosted;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $rootDiscussionEntries;
}
