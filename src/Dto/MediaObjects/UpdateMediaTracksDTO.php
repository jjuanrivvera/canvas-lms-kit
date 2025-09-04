<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\MediaObjects;

use CanvasLMS\Dto\AbstractBaseDto;
use InvalidArgumentException;

/**
 * UpdateMediaTracksDTO
 *
 * Data Transfer Object for updating MediaTracks
 *
 * @package CanvasLMS\Dto\MediaObjects
 */
class UpdateMediaTracksDTO extends AbstractBaseDto
{
    /**
     * Valid track kinds
     */
    private const VALID_KINDS = [
        'subtitles',
        'captions',
        'descriptions',
        'chapters',
        'metadata'
    ];

    /** @var array<array<string, mixed>> */
    public array $tracks = [];

    /**
     * Constructor
     *
     * @param array<array<string, mixed>> $tracks Array of track data
     */
    public function __construct(array $tracks = [])
    {
        $this->tracks = $tracks;
        $this->validate();
    }

    /**
     * Validate the DTO data
     *
     * @throws InvalidArgumentException
     */
    protected function validate(): void
    {
        if (empty($this->tracks)) {
            // Empty array is valid - it will delete all existing tracks
            return;
        }

        foreach ($this->tracks as $index => $track) {
            // Validate track structure
            if (!is_array($track)) {
                throw new InvalidArgumentException("Track at index {$index} must be an array");
            }

            // Locale is always required
            if (!isset($track['locale']) || empty($track['locale'])) {
                throw new InvalidArgumentException("Track at index {$index} must have a locale");
            }

            // If content is provided, validate it's not empty
            if (isset($track['content']) && trim($track['content']) === '') {
                throw new InvalidArgumentException("Track at index {$index} has empty content");
            }

            // If kind is provided, validate it's valid
            if (isset($track['kind']) && !in_array($track['kind'], self::VALID_KINDS)) {
                throw new InvalidArgumentException(
                    "Track at index {$index} has invalid kind. Valid kinds are: " .
                    implode(', ', self::VALID_KINDS)
                );
            }

            // Validate locale format (basic check for xx or xx-XX format)
            if (!preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $track['locale'])) {
                throw new InvalidArgumentException(
                    "Track at index {$index} has invalid locale format. " .
                    "Expected format like 'en' or 'en-US'"
                );
            }
        }
    }

    /**
     * Convert the DTO to an array for API request
     *
     * @return array<array<string, mixed>>
     */
    public function toArray(): array
    {
        // Return the tracks array directly for the API
        return $this->tracks;
    }

    /**
     * Create DTO from array
     *
     * @param array<array<string, mixed>> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        // If data has a 'tracks' key, use it; otherwise assume the whole array is tracks
        $tracks = isset($data['tracks']) ? $data['tracks'] : $data;

        return new self(tracks: $tracks);
    }

    /**
     * Add a track to the DTO
     *
     * @param string $locale The locale code
     * @param string|null $content The track content (SRT format)
     * @param string|null $kind The track kind
     * @return self
     */
    public function addTrack(string $locale, ?string $content = null, ?string $kind = null): self
    {
        $track = ['locale' => $locale];

        if ($content !== null) {
            $track['content'] = $content;
        }

        if ($kind !== null) {
            $track['kind'] = $kind;
        }

        $this->tracks[] = $track;
        $this->validate();

        return $this;
    }

    /**
     * Clear all tracks
     *
     * @return self
     */
    public function clearTracks(): self
    {
        $this->tracks = [];
        return $this;
    }
}
