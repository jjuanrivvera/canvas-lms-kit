<?php

declare(strict_types=1);

namespace Tests\Objects;

use CanvasLMS\Objects\UserDisplay;
use PHPUnit\Framework\TestCase;

class UserDisplayTest extends TestCase
{
    public function testConstructorWithFullData(): void
    {
        $data = [
            'id' => 42,
            'display_name' => 'Jane Lecturer',
            'avatar_image_url' => 'https://canvas.example.com/avatars/42.png',
            'html_url' => 'https://canvas.example.com/users/42',
            'pronouns' => 'she/her',
            'anonymous_id' => 'abc123',
        ];

        $userDisplay = new UserDisplay($data);

        $this->assertSame(42, $userDisplay->id);
        $this->assertSame('Jane Lecturer', $userDisplay->displayName);
        $this->assertSame('https://canvas.example.com/avatars/42.png', $userDisplay->avatarImageUrl);
        $this->assertSame('https://canvas.example.com/users/42', $userDisplay->htmlUrl);
        $this->assertSame('she/her', $userDisplay->pronouns);
        $this->assertSame('abc123', $userDisplay->anonymousId);
    }

    public function testConstructorWithEmptyData(): void
    {
        $userDisplay = new UserDisplay();

        $this->assertNull($userDisplay->id);
        $this->assertNull($userDisplay->displayName);
        $this->assertNull($userDisplay->avatarImageUrl);
        $this->assertNull($userDisplay->htmlUrl);
        $this->assertNull($userDisplay->pronouns);
        $this->assertNull($userDisplay->anonymousId);
    }

    public function testConstructorIgnoresUnknownFields(): void
    {
        $userDisplay = new UserDisplay([
            'id' => 7,
            'display_name' => 'John Uploader',
            'unknown_field' => 'ignored',
        ]);

        $this->assertSame(7, $userDisplay->id);
        $this->assertSame('John Uploader', $userDisplay->displayName);
        $this->assertFalse(property_exists($userDisplay, 'unknownField'));
    }

    public function testConstructorIgnoresNullValues(): void
    {
        $userDisplay = new UserDisplay([
            'id' => 7,
            'display_name' => null,
        ]);

        $this->assertSame(7, $userDisplay->id);
        $this->assertNull($userDisplay->displayName);
    }
}
