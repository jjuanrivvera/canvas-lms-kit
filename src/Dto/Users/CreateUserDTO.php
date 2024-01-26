<?php

namespace CanvasLMS\Dto\Users;

use CanvasLMS\Dto\BaseDto;
use CanvasLMS\Interfaces\DTOInterface;

class CreateUserDTO extends BaseDto implements DTOInterface
{
    public string $name;
    public ?string $shortName;
    public ?string $sortableName;
    public ?string $timeZone;
    public ?string $locale;
    public bool $termsOfUse;
    public bool $skipRegistration;
    public string $uniqueId;
    public ?string $password;
    public ?string $sisUserId;
    public ?string $integrationId;
    public bool $sendConfirmation;
    public bool $forceSelfRegistration;
    public ?string $authenticationProviderId;
    public string $communicationType;
    public string $communicationAddress;
    public ?bool $confirmationUrl;
    public bool $skipConfirmation;
    public ?bool $forceValidations;
    public bool $enableSisReactivation;
    public ?string $destination;
    public ?string $initialEnrollmentType;
    public ?string $pairingCode;

    public function toApiArray(): array
    {
        $properties = get_object_vars($this);

        $modifiedProperties = [];

        foreach ($properties as $key => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format(\DateTime::ATOM); // Convert DateTime to ISO 8601 string
            }

            if (empty($value)) {
                continue;
            }

            // The user-related properties need to be wrapped under the 'user' key
            // The pseudonym and communication channel properties will be wrapped under their respective keys
            $apiKeyName = match($key) {
                'uniqueId', 'password', 'sisUserId', 'integrationId', 
                'sendConfirmation', 'forceSelfRegistration', 'authenticationProviderId' => 'pseudonym[' . str_to_snake_case($key) . ']',
                'communicationType', 'communicationAddress', 'confirmationUrl', 'skipConfirmation' => 'communication_channel[' . str_to_snake_case(substr($key, strlen('communication'))) . ']',
                'destination', 'initialEnrollmentType', 'pairingCode' => str_to_snake_case($key),
                default => 'user[' . str_to_snake_case($key) . ']'
            };

            $modifiedProperties[] = [
                "name" => $apiKeyName,
                "contents" => $value
            ];
        }

        return $modifiedProperties;
    }
}
