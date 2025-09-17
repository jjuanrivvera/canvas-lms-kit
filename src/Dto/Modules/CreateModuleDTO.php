<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Modules;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;
use DateTimeInterface;

class CreateModuleDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The name of the property in the API
     *
     * @var string
     */
    protected string $apiPropertyName = 'module';

    /**
     * @var string
     * The name of the module
     */
    public $name;

    /**
     * @var DateTimeInterface|null
     * The date the module will unlock
     */
    public $unlockAt;

    /**
     * @var int
     * The position of this module in the course (1-based)
     */
    public $position;

    /**
     * @var bool
     * Whether module items must be unlocked in order
     */
    public $requireSequentialProgress;

    /**
     * @var mixed[]
     * IDs of Modules that must be completed before this one is unlocked.
     * Prerequisite modules must precede this module (i.e. have a lower position value), otherwise they will be ignored
     */
    public $prerequisiteModuleIds;

    /**
     * @var bool
     * Whether to publish the student’s final grade for the course upon completion of this module.
     */
    public $publishFinalGrade;
}
