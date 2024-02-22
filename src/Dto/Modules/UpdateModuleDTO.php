<?php

namespace CanvasLMS\Dto\Modules;

use DateTimeInterface;
use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class UpdateModuleDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the property in the API
     * @var string
     */
    protected string $apiPropertyName = 'module';

    /**
     * The name of the module
     * @var string|null
     */
    public ?string $name = null;

    /**
     * The date the module will unlock
     * @var DateTimeInterface|null
     */
    public ?DateTimeInterface $unlockAt = null;

    /**
     * The position of the module in the course (1-based)
     * @var int|null
     */
    public ?int $position = null;

    /**
     * Whether module items must be unlocked in order
     * @var bool|null
     */
    public ?bool $requireSequentialProgress = null;

    /**
     * IDs of Modules that must be completed before this one is unlocked
     * Prerequisite modules must precede this module (i.e. have a lower position value), otherwise they will be ignored
     * @var mixed[]|null
     */
    public ?array $prerequisiteModuleIds = null;

    /**
     * Whether to publish the student’s final grade for the course upon completion of this module.
     * @var bool|null
     */
    public ?bool $publishFinalGrade = null;

    /**
     * Whether the module is published and visible to students
     * @var bool|null
     */
    public ?bool $published = null;
}
