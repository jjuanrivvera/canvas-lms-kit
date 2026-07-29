<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use CanvasLMS\Exceptions\CanvasApiException;
use CanvasLMS\Exceptions\MissingOAuthTokenException;
use CanvasLMS\Exceptions\OAuthRefreshFailedException;
use CanvasLMS\Exceptions\OAuthTokenExpiredException;
use PHPUnit\Framework\TestCase;

class CanvasApiExceptionTest extends TestCase
{
    public function testMessageCodeAndErrorsAreExposed(): void
    {
        $errors = [
            ['message' => 'Course name is required', 'field' => 'name'],
            ['message' => 'Course code is required', 'field' => 'course_code'],
        ];

        $exception = new CanvasApiException('Validation failed', 422, $errors);

        $this->assertSame('Validation failed', $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function testDefaultsToEmptyMessageCodeAndErrors(): void
    {
        $exception = new CanvasApiException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame([], $exception->getErrors());
    }

    public function testIsCatchableAsPlainException(): void
    {
        $this->assertInstanceOf(\Exception::class, new CanvasApiException('boom'));
    }

    public function testOAuthExceptionsExtendCanvasApiException(): void
    {
        // Catching CanvasApiException must cover the OAuth failure family
        $this->assertInstanceOf(CanvasApiException::class, new OAuthRefreshFailedException());
        $this->assertInstanceOf(CanvasApiException::class, new OAuthTokenExpiredException());
        $this->assertInstanceOf(CanvasApiException::class, new MissingOAuthTokenException());
    }

    public function testOAuthExceptionsProvideDefaultMessages(): void
    {
        $this->assertSame(
            'Failed to refresh OAuth token',
            (new OAuthRefreshFailedException())->getMessage()
        );
        $this->assertSame(
            'OAuth token has expired and could not be refreshed. Please re-authenticate.',
            (new OAuthTokenExpiredException())->getMessage()
        );
        $this->assertStringContainsString(
            'OAuth token not configured',
            (new MissingOAuthTokenException())->getMessage()
        );
    }

    public function testOAuthExceptionsAcceptCustomMessages(): void
    {
        $exception = new OAuthRefreshFailedException('Token refresh failed: invalid_grant');

        $this->assertSame('Token refresh failed: invalid_grant', $exception->getMessage());
    }
}
