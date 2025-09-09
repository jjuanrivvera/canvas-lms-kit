<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\DeveloperKeys;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;

/**
 * DTO for creating Canvas Developer Keys
 *
 * Handles Canvas API key creation parameters following the Canvas API specification.
 * All parameters are nested under the 'developer_key' wrapper in the API request.
 *
 * @see https://canvas.instructure.com/doc/api/developer_keys.html#method.developer_keys.create
 */
class CreateDeveloperKeyDTO extends AbstractBaseDto implements DTOInterface
{
    protected string $apiPropertyName = 'developer_key';

    // Basic configuration
    public ?string $name = null;
    public ?string $email = null;
    public ?string $iconUrl = null;
    public ?string $notes = null;
    public ?string $vendorCode = null;
    public ?bool $visible = null;

    // OAuth configuration
    public ?string $redirectUri = null; // Deprecated in favor of redirectUris
    /** @var array<string>|null */
    public ?array $redirectUris = null;
    /** @var array<string>|null */
    public ?array $scopes = null;
    public ?bool $requireScopes = null;
    public ?bool $allowIncludes = null;

    // Security and testing
    public ?bool $testClusterOnly = null;
    public ?bool $autoExpireTokens = null;

    // OAuth2 client credentials flow
    public ?string $clientCredentialsAudience = null;

    /**
     * Create DTO instance from array data
     *
     * @param array<string, mixed> $data Input data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Convert to API array format for Canvas requests
     * Handles array properties and null value filtering
     *
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        $data = [];

        // Basic configuration
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->iconUrl !== null) {
            $data['icon_url'] = $this->iconUrl;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->vendorCode !== null) {
            $data['vendor_code'] = $this->vendorCode;
        }
        if ($this->visible !== null) {
            $data['visible'] = $this->visible;
        }

        // OAuth configuration
        if ($this->redirectUri !== null) {
            $data['redirect_uri'] = $this->redirectUri;
        }
        if ($this->redirectUris !== null && !empty($this->redirectUris)) {
            $data['redirect_uris'] = $this->redirectUris;
        }
        if ($this->scopes !== null && !empty($this->scopes)) {
            $data['scopes'] = $this->scopes;
        }
        if ($this->requireScopes !== null) {
            $data['require_scopes'] = $this->requireScopes;
        }
        if ($this->allowIncludes !== null) {
            $data['allow_includes'] = $this->allowIncludes;
        }

        // Security and testing
        if ($this->testClusterOnly !== null) {
            $data['test_cluster_only'] = $this->testClusterOnly;
        }
        if ($this->autoExpireTokens !== null) {
            $data['auto_expire_tokens'] = $this->autoExpireTokens;
        }

        // OAuth2 client credentials flow
        if ($this->clientCredentialsAudience !== null) {
            $data['client_credentials_audience'] = $this->clientCredentialsAudience;
        }

        return [
            $this->apiPropertyName => $data
        ];
    }

    /**
     * Convert to multipart array format for HTTP requests
     *
     * @return array<array<string, mixed>>
     */
    public function toMultipartArray(): array
    {
        $multipart = [];
        $data = $this->toApiArray()[$this->apiPropertyName];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Handle array values (scopes, redirect_uris)
                foreach ($value as $index => $item) {
                    $multipart[] = [
                        'name' => "{$this->apiPropertyName}[{$key}][{$index}]",
                        'contents' => (string) $item
                    ];
                }
            } else {
                $multipart[] = [
                    'name' => "{$this->apiPropertyName}[{$key}]",
                    'contents' => (string) $value
                ];
            }
        }

        return $multipart;
    }

    /**
     * Set the developer key name
     *
     * @param string|null $name The display name for the developer key
     * @return self
     */
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the contact email
     *
     * @param string|null $email Contact email for the key
     * @return self
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Set the icon URL
     *
     * @param string|null $iconUrl URL for a small icon to display in key list
     * @return self
     */
    public function setIconUrl(?string $iconUrl): self
    {
        $this->iconUrl = $iconUrl;
        return $this;
    }

    /**
     * Set user notes about the key
     *
     * @param string|null $notes User-provided notes about the key
     * @return self
     */
    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Set the vendor code
     *
     * @param string|null $vendorCode User-specified code representing the vendor
     * @return self
     */
    public function setVendorCode(?string $vendorCode): self
    {
        $this->vendorCode = $vendorCode;
        return $this;
    }

    /**
     * Set key visibility
     *
     * @param bool|null $visible If false, key will not be visible in the UI
     * @return self
     */
    public function setVisible(?bool $visible): self
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Set redirect URIs for OAuth2 flow
     *
     * @param array<string>|null $redirectUris List of URLs used during OAuth2 flow
     * @return self
     */
    public function setRedirectUris(?array $redirectUris): self
    {
        $this->redirectUris = $redirectUris;
        return $this;
    }

    /**
     * Set API scopes
     *
     * @param array<string>|null $scopes List of API endpoints key is allowed to access
     * @return self
     */
    public function setScopes(?array $scopes): self
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * Set whether scopes are required
     *
     * @param bool|null $requireScopes If true, token requests must include scopes
     * @return self
     */
    public function setRequireScopes(?bool $requireScopes): self
    {
        $this->requireScopes = $requireScopes;
        return $this;
    }

    /**
     * Set whether includes are allowed
     *
     * @param bool|null $allowIncludes If true, allows 'includes' parameters in API requests
     * @return self
     */
    public function setAllowIncludes(?bool $allowIncludes): self
    {
        $this->allowIncludes = $allowIncludes;
        return $this;
    }

    /**
     * Set test cluster restriction
     *
     * @param bool|null $testClusterOnly If true, key only works in test/beta environments
     * @return self
     */
    public function setTestClusterOnly(?bool $testClusterOnly): self
    {
        $this->testClusterOnly = $testClusterOnly;
        return $this;
    }

    /**
     * Set auto-expire tokens setting
     *
     * @param bool|null $autoExpireTokens If true, tokens expire after 1 hour
     * @return self
     */
    public function setAutoExpireTokens(?bool $autoExpireTokens): self
    {
        $this->autoExpireTokens = $autoExpireTokens;
        return $this;
    }

    /**
     * Set client credentials audience
     *
     * @param string|null $audience Audience for OAuth2 client credentials flow
     * @return self
     */
    public function setClientCredentialsAudience(?string $audience): self
    {
        $this->clientCredentialsAudience = $audience;
        return $this;
    }

    /**
     * Add a single redirect URI
     *
     * @param string $uri Redirect URI to add
     * @return self
     */
    public function addRedirectUri(string $uri): self
    {
        if ($this->redirectUris === null) {
            $this->redirectUris = [];
        }
        $this->redirectUris[] = $uri;
        return $this;
    }

    /**
     * Add a single scope
     *
     * @param string $scope API scope to add
     * @return self
     */
    public function addScope(string $scope): self
    {
        if ($this->scopes === null) {
            $this->scopes = [];
        }
        $this->scopes[] = $scope;
        return $this;
    }
}
