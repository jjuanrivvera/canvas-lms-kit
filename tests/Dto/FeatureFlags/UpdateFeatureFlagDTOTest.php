<?php

declare(strict_types=1);

namespace CanvasLMS\Tests\Dto\FeatureFlags;

use CanvasLMS\Dto\FeatureFlags\UpdateFeatureFlagDTO;
use PHPUnit\Framework\TestCase;

class UpdateFeatureFlagDTOTest extends TestCase
{
    public function testConstructorWithState(): void
    {
        $dto = new UpdateFeatureFlagDTO(['state' => 'on']);

        $this->assertEquals('on', $dto->getState());
        $this->assertNull($dto->isLocked());
        $this->assertNull($dto->isHidden());
    }

    public function testConstructorWithAllFields(): void
    {
        $dto = new UpdateFeatureFlagDTO([
            'state' => 'allowed',
            'locked' => true,
            'hidden' => false,
        ]);

        $this->assertEquals('allowed', $dto->getState());
        $this->assertTrue($dto->isLocked());
        $this->assertFalse($dto->isHidden());
    }

    public function testConstructorWithInvalidState(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid feature flag state "invalid". Valid states are: off, allowed, on');

        new UpdateFeatureFlagDTO(['state' => 'invalid']);
    }

    public function testSetState(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setState('on');

        $this->assertEquals('on', $dto->getState());
    }

    public function testSetStateWithInvalidValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid feature flag state "wrong". Valid states are: off, allowed, on');

        $dto = new UpdateFeatureFlagDTO();
        $dto->setState('wrong');
    }

    public function testEnableMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->enable();

        $this->assertEquals('on', $dto->getState());
    }

    public function testDisableMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->disable();

        $this->assertEquals('off', $dto->getState());
    }

    public function testAllowMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->allow();

        $this->assertEquals('allowed', $dto->getState());
    }

    public function testSetLocked(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setLocked(true);

        $this->assertTrue($dto->isLocked());

        $dto->setLocked(false);

        $this->assertFalse($dto->isLocked());
    }

    public function testLockMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->lock();

        $this->assertTrue($dto->isLocked());
    }

    public function testUnlockMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->lock();
        $dto->unlock();

        $this->assertFalse($dto->isLocked());
    }

    public function testSetHidden(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setHidden(true);

        $this->assertTrue($dto->isHidden());

        $dto->setHidden(false);

        $this->assertFalse($dto->isHidden());
    }

    public function testHideMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->hide();

        $this->assertTrue($dto->isHidden());
    }

    public function testShowMethod(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->hide();
        $dto->show();

        $this->assertFalse($dto->isHidden());
    }

    public function testToMultipartWithState(): void
    {
        $dto = new UpdateFeatureFlagDTO(['state' => 'on']);
        $multipart = $dto->toMultipart();

        $this->assertCount(1, $multipart);
        $this->assertEquals('state', $multipart[0]['name']);
        $this->assertEquals('on', $multipart[0]['contents']);
    }

    public function testToMultipartWithAllFields(): void
    {
        $dto = new UpdateFeatureFlagDTO([
            'state' => 'allowed',
            'locked' => true,
            'hidden' => false,
        ]);
        $multipart = $dto->toMultipart();

        $this->assertCount(3, $multipart);

        $fields = [];
        foreach ($multipart as $part) {
            $fields[$part['name']] = $part['contents'];
        }

        $this->assertEquals('allowed', $fields['state']);
        $this->assertEquals('true', $fields['locked']);
        $this->assertEquals('false', $fields['hidden']);
    }

    public function testToMultipartWithOnlyLocked(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setLocked(true);
        $multipart = $dto->toMultipart();

        $this->assertCount(1, $multipart);
        $this->assertEquals('locked', $multipart[0]['name']);
        $this->assertEquals('true', $multipart[0]['contents']);
    }

    public function testToMultipartWithOnlyHidden(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setHidden(false);
        $multipart = $dto->toMultipart();

        $this->assertCount(1, $multipart);
        $this->assertEquals('hidden', $multipart[0]['name']);
        $this->assertEquals('false', $multipart[0]['contents']);
    }

    public function testToArray(): void
    {
        $dto = new UpdateFeatureFlagDTO([
            'state' => 'on',
            'locked' => true,
            'hidden' => false,
        ]);

        $array = $dto->toArray();

        $this->assertEquals([
            'state' => 'on',
            'locked' => true,
            'hidden' => false,
        ], $array);
    }

    public function testToArrayWithPartialFields(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setState('allowed');
        $dto->setLocked(false);

        $array = $dto->toArray();

        $this->assertEquals([
            'state' => 'allowed',
            'locked' => false,
        ], $array);
    }

    public function testToArrayWithEmptyDTO(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $array = $dto->toArray();

        $this->assertEquals([], $array);
    }

    public function testIsValidWithState(): void
    {
        $dto = new UpdateFeatureFlagDTO(['state' => 'on']);

        $this->assertTrue($dto->isValid());
    }

    public function testIsValidWithLocked(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setLocked(true);

        $this->assertTrue($dto->isValid());
    }

    public function testIsValidWithHidden(): void
    {
        $dto = new UpdateFeatureFlagDTO();
        $dto->setHidden(false);

        $this->assertTrue($dto->isValid());
    }

    public function testIsValidWithEmptyDTO(): void
    {
        $dto = new UpdateFeatureFlagDTO();

        $this->assertFalse($dto->isValid());
    }

    public function testFluentInterface(): void
    {
        $dto = new UpdateFeatureFlagDTO();

        $result = $dto
            ->setState('allowed')
            ->setLocked(true)
            ->setHidden(false);

        $this->assertInstanceOf(UpdateFeatureFlagDTO::class, $result);
        $this->assertEquals('allowed', $dto->getState());
        $this->assertTrue($dto->isLocked());
        $this->assertFalse($dto->isHidden());
    }

    public function testValidStateValues(): void
    {
        $validStates = ['off', 'allowed', 'on'];

        foreach ($validStates as $state) {
            $dto = new UpdateFeatureFlagDTO(['state' => $state]);
            $this->assertEquals($state, $dto->getState());
        }
    }

    public function testChainedMethods(): void
    {
        $dto = new UpdateFeatureFlagDTO();

        $dto->enable()->lock()->hide();

        $this->assertEquals('on', $dto->getState());
        $this->assertTrue($dto->isLocked());
        $this->assertTrue($dto->isHidden());

        $dto->disable()->unlock()->show();

        $this->assertEquals('off', $dto->getState());
        $this->assertFalse($dto->isLocked());
        $this->assertFalse($dto->isHidden());
    }
}
