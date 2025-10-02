<?php

declare(strict_types=1);

namespace CanvasLMS\Dto\Users;

use CanvasLMS\Dto\AbstractBaseDto;
use CanvasLMS\Interfaces\DTOInterface;
use CanvasLMS\Utilities\Str;
use DateTimeInterface;

class CreateUserDTO extends AbstractBaseDto implements DTOInterface
{
    /**
     * The full name of the user. This name will be used by teacher for grading.
     * Required if this is a self-registration.
     *
     * @var string
     */
    public string $name;

    /**
     * User’s name as it will be displayed in discussions, messages, and comments.
     *
     * @var string|null
     */
    public ?string $shortName;

    /**
     * User’s name as used to sort alphabetically in lists.
     *
     * @var string|null
     */
    public ?string $sortableName;

    /**
     * The time zone for the user. Allowed time zones are IANA time zones or friendlier Ruby on Rails time zones.
     *
     * @var string|null
     */
    public ?string $timeZone;

    /**
     * The user's preferred language, from the list of languages Canvas supports. This is in RFC-5646 format.
     *
     * @var string|null
     */
    public ?string $locale;

    /**
     * The user's birth date.
     *
     * @var DateTimeInterface|null
     */
    public ?DateTimeInterface $birthdate = null;

    /**
     * Whether the user accepts the terms of use. Required if this is a self-registration and
     * this canvas instance requires users to accept the terms (on by default).
     * If this is true, it will mark the user as having accepted the terms of use.
     *
     * @var bool
     */
    public bool $termsOfUse;

    /**
     * Automatically mark the user as registered.
     * If this is true, it is recommended to set "pseudonym[send_confirmation]" to true as well.
     * Otherwise, the user will not receive any messages about their account creation.
     * The users communication channel confirmation can be skipped by setting
     * "communication_channel[skip_confirmation]" to true as well.
     *
     * @var bool
     */
    public bool $skipRegistration;

    /**
     * User’s login ID. If this is a self-registration, it must be a valid email address.
     *
     * @var string
     */
    public string $uniqueId;

    /**
     * User’s password. Cannot be set during self-registration.
     *
     * @var string|null
     */
    public ?string $password;

    /**
     * SIS ID for the user’s account. To set this parameter, the caller must be able to manage SIS permissions.
     *
     * @var string|null
     */
    public ?string $sisUserId;

    /**
     * Integration ID for the login. To set this parameter, the caller must be able to manage SIS permissions.
     * The Integration ID is a secondary identifier useful for more complex SIS integrations.
     *
     * @var string|null
     */
    public ?string $integrationId;

    /**
     * Send user notification of account creation if true. Automatically set to true during self-registration.
     *
     * @var bool
     */
    public bool $sendConfirmation;

    /**
     * Send user a self-registration style email if true.
     * Setting it means the users will get a notification asking them to “complete the registration process”
     * by clicking it, setting a password, and letting them in.
     * Will only be executed on if the user does not need admin approval.
     * Defaults to false unless explicitly provided.
     *
     * @var bool
     */
    public bool $forceSelfRegistration;

    /**
     * The authentication provider this login is associated with.
     * Logins associated with a specific provider can only be used with that provider.
     * Legacy providers (LDAP, CAS, SAML) will search for logins associated with them, or unassociated logins.
     * New providers will only search for logins explicitly associated with them.
     * This can be the integer ID of the provider, or the type of the provider
     * (in which case, it will find the first matching provider).
     *
     * @var string|null
     */
    public ?string $authenticationProviderId;

    /**
     * Send users a self-registration style email if true. Setting it means the users
     * will get password-setting info. If false, notification_email is sent instead.
     * Only valid if the sending_confirmation option is set.
     *
     * @var string|null
     */
    public ?string $declaredUserType = null;

    /**
     * The communication channel type, e.g. 'email' or 'sms'.
     *
     * @var string
     */
    public string $communicationType;

    /**
     * The communication channel address, e.g. the user’s email address.
     *
     * @var string
     */
    public string $communicationAddress;

    /**
     * Only valid for account admins. If true, returns the new user account confirmation URL in the response.
     *
     * @var bool|null
     */
    public ?bool $confirmationUrl;

    /**
     * Only valid for site admins and account admins making requests;
     * If true, the channel is automatically validated and no confirmation email or SMS is sent.
     * Otherwise, the user must respond to a confirmation message to confirm the channel.
     * If this is true, it is recommended to set "pseudonym[send_confirmation]" to true as well.
     * Otherwise, the user will not receive any messages about their account creation.
     *
     * @var bool
     */
    public bool $skipConfirmation;

    /**
     * If true, validations are performed on the newly created user (and their associated pseudonym)
     * even if the request is made by a privileged user like an admin. When set to false,
     * or not included in the request parameters,any newly created users are subject to validations
     * unless the request is made by a user with a 'manage_user_logins' right. In which case, certain
     * validations such as 'require_acceptance_of_terms' and 'require_presence_of_name' are not enforced.
     * Use this parameter to return helpful json errors while building users with an admin request.
     *
     * @var bool|null
     */
    public ?bool $forceValidations;

    /**
     * When true, will first try to re-activate a deleted user with matching sis_user_id if possible.
     * This is commonly done with user and communication_channel so that the default communication_channel
     * is also restored.
     *
     * @var bool
     */
    public bool $enableSisReactivation;

    /**
     * If you’re setting the password for the newly created user, you can provide this param with a
     * valid URL pointing into this Canvas installation, and the response will include a destination
     * field that’s a URL that you can redirect a browser to and have the newly created user
     * automatically logged in.  The URL is only valid for a short time,
     * and must match the domain this request is directed to, and be for a well-formed path that Canvas can recognize.
     *
     * @var string|null
     */
    public ?string $destination;

    /**
     * ‘observer` if doing a self-registration with a pairing code.
     * This allows setting the password during user creation.
     *
     * @var string|null
     */
    public ?string $initialEnrollmentType;

    /**
     * If provided and valid, will link the new user as an observer to the student’s whose pairing code is given.
     *
     * @var string|null
     */
    public ?string $pairingCode;

    public function toApiArray(): array
    {
        $properties = get_object_vars($this);

        $modifiedProperties = [];

        foreach ($properties as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d'); // Convert DateTime to YYYY-MM-DD format for birthdate
            }

            if (empty($value)) {
                continue;
            }

            // The user-related properties need to be wrapped under the 'user' key
            // The pseudonym and communication channel properties will be wrapped under their respective keys
            $apiKeyName = match ($key) {
                'uniqueId',
                'password',
                'sisUserId',
                'integrationId',
                'sendConfirmation',
                'forceSelfRegistration',
                'authenticationProviderId',
                'declaredUserType' => 'pseudonym[' . Str::toSnakeCase($key) . ']',
                'communicationType',
                'communicationAddress',
                'confirmationUrl',
                'skipConfirmation' =>
                'communication_channel[' . Str::toSnakeCase(substr($key, strlen('communication'))) . ']',
                'destination',
                'initialEnrollmentType',
                'pairingCode',
                'forceValidations',
                'enableSisReactivation' => Str::toSnakeCase($key),
                default => 'user[' . Str::toSnakeCase($key) . ']'
            };

            $modifiedProperties[] = [
                'name' => $apiKeyName,
                'contents' => $value,
            ];
        }

        return $modifiedProperties;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @param string|null $shortName
     */
    public function setShortName(?string $shortName): void
    {
        $this->shortName = $shortName;
    }

    /**
     * @return string|null
     */
    public function getSortableName(): ?string
    {
        return $this->sortableName;
    }

    /**
     * @param string|null $sortableName
     */
    public function setSortableName(?string $sortableName): void
    {
        $this->sortableName = $sortableName;
    }

    /**
     * @return string|null
     */
    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    /**
     * @param string|null $timeZone
     */
    public function setTimeZone(?string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     */
    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @return bool
     */
    public function isTermsOfUse(): bool
    {
        return $this->termsOfUse;
    }

    /**
     * @param bool $termsOfUse
     */
    public function setTermsOfUse(bool $termsOfUse): void
    {
        $this->termsOfUse = $termsOfUse;
    }

    /**
     * @return bool
     */
    public function isSkipRegistration(): bool
    {
        return $this->skipRegistration;
    }

    /**
     * @param bool $skipRegistration
     */
    public function setSkipRegistration(bool $skipRegistration): void
    {
        $this->skipRegistration = $skipRegistration;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     */
    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getSisUserId(): ?string
    {
        return $this->sisUserId;
    }

    /**
     * @param string|null $sisUserId
     */
    public function setSisUserId(?string $sisUserId): void
    {
        $this->sisUserId = $sisUserId;
    }

    /**
     * @return string|null
     */
    public function getIntegrationId(): ?string
    {
        return $this->integrationId;
    }

    /**
     * @param string|null $integrationId
     */
    public function setIntegrationId(?string $integrationId): void
    {
        $this->integrationId = $integrationId;
    }

    /**
     * @return bool
     */
    public function isSendConfirmation(): bool
    {
        return $this->sendConfirmation;
    }

    /**
     * @param bool $sendConfirmation
     */
    public function setSendConfirmation(bool $sendConfirmation): void
    {
        $this->sendConfirmation = $sendConfirmation;
    }

    /**
     * @return bool
     */
    public function isForceSelfRegistration(): bool
    {
        return $this->forceSelfRegistration;
    }

    /**
     * @param bool $forceSelfRegistration
     */
    public function setForceSelfRegistration(bool $forceSelfRegistration): void
    {
        $this->forceSelfRegistration = $forceSelfRegistration;
    }

    /**
     * @return string|null
     */
    public function getAuthenticationProviderId(): ?string
    {
        return $this->authenticationProviderId;
    }

    /**
     * @param string|null $authenticationProviderId
     */
    public function setAuthenticationProviderId(?string $authenticationProviderId): void
    {
        $this->authenticationProviderId = $authenticationProviderId;
    }

    /**
     * @return string
     */
    public function getCommunicationType(): string
    {
        return $this->communicationType;
    }

    /**
     * @param string $communicationType
     */
    public function setCommunicationType(string $communicationType): void
    {
        $this->communicationType = $communicationType;
    }

    /**
     * @return string
     */
    public function getCommunicationAddress(): string
    {
        return $this->communicationAddress;
    }

    /**
     * @param string $communicationAddress
     */
    public function setCommunicationAddress(string $communicationAddress): void
    {
        $this->communicationAddress = $communicationAddress;
    }

    /**
     * @return bool|null
     */
    public function getConfirmationUrl(): ?bool
    {
        return $this->confirmationUrl;
    }

    /**
     * @param bool|null $confirmationUrl
     */
    public function setConfirmationUrl(?bool $confirmationUrl): void
    {
        $this->confirmationUrl = $confirmationUrl;
    }

    /**
     * @return bool
     */
    public function isSkipConfirmation(): bool
    {
        return $this->skipConfirmation;
    }

    /**
     * @param bool $skipConfirmation
     */
    public function setSkipConfirmation(bool $skipConfirmation): void
    {
        $this->skipConfirmation = $skipConfirmation;
    }

    /**
     * @return bool|null
     */
    public function getForceValidations(): ?bool
    {
        return $this->forceValidations;
    }

    /**
     * @param bool|null $forceValidations
     */
    public function setForceValidations(?bool $forceValidations): void
    {
        $this->forceValidations = $forceValidations;
    }

    /**
     * @return bool
     */
    public function isEnableSisReactivation(): bool
    {
        return $this->enableSisReactivation;
    }

    /**
     * @param bool $enableSisReactivation
     */
    public function setEnableSisReactivation(bool $enableSisReactivation): void
    {
        $this->enableSisReactivation = $enableSisReactivation;
    }

    /**
     * @return string|null
     */
    public function getDestination(): ?string
    {
        return $this->destination;
    }

    /**
     * @param string|null $destination
     */
    public function setDestination(?string $destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @return string|null
     */
    public function getInitialEnrollmentType(): ?string
    {
        return $this->initialEnrollmentType;
    }

    /**
     * @param string|null $initialEnrollmentType
     */
    public function setInitialEnrollmentType(?string $initialEnrollmentType): void
    {
        $this->initialEnrollmentType = $initialEnrollmentType;
    }

    /**
     * @return string|null
     */
    public function getPairingCode(): ?string
    {
        return $this->pairingCode;
    }

    /**
     * @param string|null $pairingCode
     */
    public function setPairingCode(?string $pairingCode): void
    {
        $this->pairingCode = $pairingCode;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getBirthdate(): ?DateTimeInterface
    {
        return $this->birthdate;
    }

    /**
     * @param DateTimeInterface|null $birthdate
     */
    public function setBirthdate(?DateTimeInterface $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    /**
     * @return string|null
     */
    public function getDeclaredUserType(): ?string
    {
        return $this->declaredUserType;
    }

    /**
     * @param string|null $declaredUserType
     */
    public function setDeclaredUserType(?string $declaredUserType): void
    {
        $this->declaredUserType = $declaredUserType;
    }
}
