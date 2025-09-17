<?php

declare(strict_types=1);

namespace Tests\Dto\ExternalTools;

use CanvasLMS\Dto\ExternalTools\UpdateExternalToolDTO;
use PHPUnit\Framework\TestCase;

/**
 * Test class for UpdateExternalToolDTO
 *
 * @covers \CanvasLMS\Dto\ExternalTools\UpdateExternalToolDTO
 */
class UpdateExternalToolDTOTest extends TestCase
{
    public static function updateExternalToolDataProvider(): array
    {
        return [
            'name only update' => [
                ['name' => 'Updated Tool Name'],
            ],
            'privacy level update' => [
                ['privacy_level' => 'anonymous'],
            ],
            'multiple fields update' => [
                [
                    'name' => 'Updated Tool',
                    'description' => 'Updated description',
                    'privacy_level' => 'name_only',
                    'url' => 'https://updated.example.com/lti/launch',
                    'icon_url' => 'https://updated.example.com/icon.png',
                ],
            ],
            'placement configuration update' => [
                [
                    'name' => 'Tool with Navigation',
                    'course_navigation' => [
                        'enabled' => false,
                        'text' => 'Disabled Tool',
                        'visibility' => 'admins',
                    ],
                    'editor_button' => [
                        'enabled' => true,
                        'icon_url' => 'https://example.com/new_icon.png',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateExternalToolDataProvider
     */
    public function testConstructorWithValidData(array $data): void
    {
        $dto = new UpdateExternalToolDTO($data);

        if (isset($data['name'])) {
            $this->assertEquals($data['name'], $dto->getName());
        }

        if (isset($data['description'])) {
            $this->assertEquals($data['description'], $dto->getDescription());
        }

        if (isset($data['privacy_level'])) {
            $this->assertEquals($data['privacy_level'], $dto->getPrivacyLevel());
        }

        if (isset($data['url'])) {
            $this->assertEquals($data['url'], $dto->getUrl());
        }

        if (isset($data['course_navigation'])) {
            $this->assertEquals($data['course_navigation'], $dto->getCourseNavigation());
        }

        if (isset($data['editor_button'])) {
            $this->assertEquals($data['editor_button'], $dto->getEditorButton());
        }
    }

    public function testEmptyConstructor(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $this->assertNull($dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getPrivacyLevel());
        $this->assertNull($dto->getConsumerKey());
        $this->assertNull($dto->getSharedSecret());
        $this->assertNull($dto->getUrl());
        $this->assertNull($dto->getDomain());
        $this->assertNull($dto->getIconUrl());
        $this->assertNull($dto->getText());
        $this->assertNull($dto->getCustomFields());
    }

    public function testBasicGettersAndSetters(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setName('Updated Tool');
        $this->assertEquals('Updated Tool', $dto->getName());

        $dto->setDescription('Updated Description');
        $this->assertEquals('Updated Description', $dto->getDescription());

        $dto->setPrivacyLevel('anonymous');
        $this->assertEquals('anonymous', $dto->getPrivacyLevel());

        $dto->setConsumerKey('updated_key');
        $this->assertEquals('updated_key', $dto->getConsumerKey());

        $dto->setSharedSecret('updated_secret');
        $this->assertEquals('updated_secret', $dto->getSharedSecret());

        $dto->setUrl('https://updated.example.com/lti/launch');
        $this->assertEquals('https://updated.example.com/lti/launch', $dto->getUrl());

        $dto->setDomain('updated.example.com');
        $this->assertEquals('updated.example.com', $dto->getDomain());

        $dto->setIconUrl('https://updated.example.com/icon.png');
        $this->assertEquals('https://updated.example.com/icon.png', $dto->getIconUrl());

        $dto->setText('Updated Launch Text');
        $this->assertEquals('Updated Launch Text', $dto->getText());
    }

    public function testCustomFieldsGettersAndSetters(): void
    {
        $dto = new UpdateExternalToolDTO([]);
        $customFields = ['updated_key1' => 'updated_value1', 'key2' => 'value2'];

        $dto->setCustomFields($customFields);
        $this->assertEquals($customFields, $dto->getCustomFields());

        $dto->setCustomFields(null);
        $this->assertNull($dto->getCustomFields());

        $emptyFields = [];
        $dto->setCustomFields($emptyFields);
        $this->assertEquals($emptyFields, $dto->getCustomFields());
    }

    public function testBooleanGettersAndSetters(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setIsRceFavorite(false);
        $this->assertFalse($dto->getIsRceFavorite());

        $dto->setIsRceFavorite(true);
        $this->assertTrue($dto->getIsRceFavorite());

        $dto->setNotSelectable(true);
        $this->assertTrue($dto->getNotSelectable());

        $dto->setOauthCompliant(false);
        $this->assertFalse($dto->getOauthCompliant());
    }

    public function testConfigurationGettersAndSetters(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setConfigType('by_xml');
        $this->assertEquals('by_xml', $dto->getConfigType());

        $dto->setConfigXml('<updated>xml</updated>');
        $this->assertEquals('<updated>xml</updated>', $dto->getConfigXml());

        $dto->setConfigUrl('https://updated.example.com/config.xml');
        $this->assertEquals('https://updated.example.com/config.xml', $dto->getConfigUrl());

        $dto->setUnifiedToolId('updated_unified123');
        $this->assertEquals('updated_unified123', $dto->getUnifiedToolId());
    }

    public function testPlacementConfigurationGettersAndSetters(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $accountNav = ['enabled' => false, 'text' => 'Updated Account'];
        $dto->setAccountNavigation($accountNav);
        $this->assertEquals($accountNav, $dto->getAccountNavigation());

        $userNav = ['enabled' => true, 'text' => 'Updated User'];
        $dto->setUserNavigation($userNav);
        $this->assertEquals($userNav, $dto->getUserNavigation());

        $courseHomeNav = ['enabled' => true, 'text' => 'Updated Home'];
        $dto->setCourseHomeSubNavigation($courseHomeNav);
        $this->assertEquals($courseHomeNav, $dto->getCourseHomeSubNavigation());

        $courseNav = ['enabled' => false, 'visibility' => 'admins'];
        $dto->setCourseNavigation($courseNav);
        $this->assertEquals($courseNav, $dto->getCourseNavigation());

        $editorButton = ['enabled' => true, 'selection_width' => '600'];
        $dto->setEditorButton($editorButton);
        $this->assertEquals($editorButton, $dto->getEditorButton());

        $homeworkSubmission = ['enabled' => false, 'text' => 'Updated Homework'];
        $dto->setHomeworkSubmission($homeworkSubmission);
        $this->assertEquals($homeworkSubmission, $dto->getHomeworkSubmission());

        $linkSelection = ['enabled' => true, 'message_type' => 'ContentItemSelectionRequest'];
        $dto->setLinkSelection($linkSelection);
        $this->assertEquals($linkSelection, $dto->getLinkSelection());

        $migrationSelection = ['enabled' => false];
        $dto->setMigrationSelection($migrationSelection);
        $this->assertEquals($migrationSelection, $dto->getMigrationSelection());

        $toolConfiguration = ['enabled' => true, 'prefer_sis_email' => true];
        $dto->setToolConfiguration($toolConfiguration);
        $this->assertEquals($toolConfiguration, $dto->getToolConfiguration());

        $resourceSelection = ['enabled' => false, 'selection_height' => '500'];
        $dto->setResourceSelection($resourceSelection);
        $this->assertEquals($resourceSelection, $dto->getResourceSelection());
    }

    public function testAccountNavigationHelperMethods(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setAccountNavigationUrl('https://updated.example.com/account');
        $accountNav = $dto->getAccountNavigation();
        $this->assertEquals('https://updated.example.com/account', $accountNav['url']);

        $dto->setAccountNavigationEnabled(false);
        $accountNav = $dto->getAccountNavigation();
        $this->assertFalse($accountNav['enabled']);

        $dto->setAccountNavigationText('Updated Account Tool');
        $accountNav = $dto->getAccountNavigation();
        $this->assertEquals('Updated Account Tool', $accountNav['text']);
    }

    public function testCourseNavigationHelperMethods(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setCourseNavigationEnabled(false);
        $courseNav = $dto->getCourseNavigation();
        $this->assertFalse($courseNav['enabled']);

        $dto->setCourseNavigationText('Updated Course Tool');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('Updated Course Tool', $courseNav['text']);

        $dto->setCourseNavigationVisibility('admins');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('admins', $courseNav['visibility']);

        $dto->setCourseNavigationWindowTarget('_self');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('_self', $courseNav['windowTarget']);

        $dto->setCourseNavigationDefault('disabled');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('disabled', $courseNav['default']);
    }

    public function testEditorButtonHelperMethods(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setEditorButtonUrl('https://updated.example.com/editor');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('https://updated.example.com/editor', $editorButton['url']);

        $dto->setEditorButtonEnabled(false);
        $editorButton = $dto->getEditorButton();
        $this->assertFalse($editorButton['enabled']);

        $dto->setEditorButtonIconUrl('https://updated.example.com/icon.png');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('https://updated.example.com/icon.png', $editorButton['icon_url']);

        $dto->setEditorButtonSelectionWidth('600');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('600', $editorButton['selection_width']);

        $dto->setEditorButtonSelectionHeight('500');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('500', $editorButton['selection_height']);

        $dto->setEditorButtonMessageType('basic-lti-launch-request');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('basic-lti-launch-request', $editorButton['message_type']);
    }

    public function testResourceSelectionHelperMethods(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setResourceSelectionUrl('https://updated.example.com/resource');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('https://updated.example.com/resource', $resourceSelection['url']);

        $dto->setResourceSelectionEnabled(false);
        $resourceSelection = $dto->getResourceSelection();
        $this->assertFalse($resourceSelection['enabled']);

        $dto->setResourceSelectionIconUrl('https://updated.example.com/resource_icon.png');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('https://updated.example.com/resource_icon.png', $resourceSelection['icon_url']);

        $dto->setResourceSelectionWidth('900');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('900', $resourceSelection['selection_width']);

        $dto->setResourceSelectionHeight('700');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('700', $resourceSelection['selection_height']);
    }

    public function testToApiArray(): void
    {
        $dto = new UpdateExternalToolDTO([
            'name' => 'Updated Tool',
            'privacy_level' => 'anonymous',
            'description' => 'Updated description',
        ]);

        $dto->setCourseNavigationEnabled(false);
        $dto->setCourseNavigationText('Disabled Tool');

        $apiArray = $dto->toApiArray();

        $this->assertIsArray($apiArray);

        foreach ($apiArray as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('contents', $item);
        }

        $foundName = false;
        $foundPrivacyLevel = false;

        foreach ($apiArray as $item) {
            if ($item['name'] === 'external_tool[name]') {
                $foundName = true;
                $this->assertEquals('Updated Tool', $item['contents']);
            }
            if ($item['name'] === 'external_tool[privacy_level]') {
                $foundPrivacyLevel = true;
                $this->assertEquals('anonymous', $item['contents']);
            }
        }

        $this->assertTrue($foundName, 'Name not found in API array');
        $this->assertTrue($foundPrivacyLevel, 'Privacy level not found in API array');
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Updated Tool',
            'privacy_level' => 'email_only',
            'consumer_key' => 'updated_key',
            'url' => 'https://updated.example.com/lti/launch',
        ];

        $dto = new UpdateExternalToolDTO($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($data['name'], $array['name']);
        $this->assertEquals($data['privacy_level'], $array['privacyLevel']);
        $this->assertEquals($data['consumer_key'], $array['consumerKey']);
        $this->assertEquals($data['url'], $array['url']);
    }

    public function testPartialUpdate(): void
    {
        $dto = new UpdateExternalToolDTO([]);

        $dto->setName('Only Name Updated');

        $this->assertEquals('Only Name Updated', $dto->getName());
        $this->assertNull($dto->getDescription());
        $this->assertNull($dto->getPrivacyLevel());
        $this->assertNull($dto->getUrl());

        $apiArray = $dto->toApiArray();
        $this->assertIsArray($apiArray);

        $nameFound = false;
        foreach ($apiArray as $item) {
            if ($item['name'] === 'external_tool[name]') {
                $nameFound = true;
                $this->assertEquals('Only Name Updated', $item['contents']);
            }
        }

        $this->assertTrue($nameFound, 'Updated name should be in API array');
    }

    public function testSnakeCaseConversion(): void
    {
        $snakeCaseData = [
            'privacy_level' => 'name_only',
            'consumer_key' => 'snake_key',
            'shared_secret' => 'snake_secret',
            'icon_url' => 'https://example.com/snake_icon.png',
            'custom_fields' => ['snake_key' => 'snake_value'],
            'not_selectable' => true,
            'oauth_compliant' => false,
            'is_rce_favorite' => true,
        ];

        $dto = new UpdateExternalToolDTO($snakeCaseData);
        $array = $dto->toArray();

        $this->assertEquals('name_only', $array['privacyLevel']);
        $this->assertEquals('snake_key', $array['consumerKey']);
        $this->assertEquals('snake_secret', $array['sharedSecret']);
        $this->assertEquals('https://example.com/snake_icon.png', $array['iconUrl']);
        $this->assertEquals(['snake_key' => 'snake_value'], $array['customFields']);
        $this->assertTrue($array['notSelectable']);
        $this->assertFalse($array['oauthCompliant']);
        $this->assertTrue($array['isRceFavorite']);
    }

    public function testComplexPlacementUpdate(): void
    {
        $courseNavConfig = [
            'enabled' => false,
            'text' => 'Disabled Tool',
            'visibility' => 'admins',
        ];

        $editorButtonConfig = [
            'enabled' => true,
            'icon_url' => 'https://updated.example.com/icon.png',
            'selection_width' => '600',
            'selection_height' => '500',
        ];

        $dto = new UpdateExternalToolDTO([
            'course_navigation' => $courseNavConfig,
            'editor_button' => $editorButtonConfig,
        ]);

        $this->assertEquals($courseNavConfig, $dto->getCourseNavigation());
        $this->assertEquals($editorButtonConfig, $dto->getEditorButton());

        $apiArray = $dto->toApiArray();
        $this->assertIsArray($apiArray);

        $foundCourseNavEnabled = false;
        $foundEditorButtonEnabled = false;

        // Note: The AbstractBaseDto handles nested arrays differently than expected
        // For now, just verify the basic structure works
        foreach ($apiArray as $item) {
            if (str_contains($item['name'], 'course_navigation')) {
                $foundCourseNavEnabled = true;
            }
            if (str_contains($item['name'], 'editor_button')) {
                $foundEditorButtonEnabled = true;
            }
        }

        // Test that nested placement configurations are properly formatted
        $foundCourseNavEnabled = false;
        $foundCourseNavText = false;
        $foundEditorButtonEnabled = false;
        $foundEditorButtonIcon = false;

        foreach ($apiArray as $item) {
            if ($item['name'] === 'external_tool[course_navigation][enabled]') {
                $foundCourseNavEnabled = true;
                $this->assertFalse($item['contents']);
            }
            if ($item['name'] === 'external_tool[course_navigation][text]') {
                $foundCourseNavText = true;
                $this->assertEquals('Disabled Tool', $item['contents']);
            }
            if ($item['name'] === 'external_tool[editor_button][enabled]') {
                $foundEditorButtonEnabled = true;
                $this->assertTrue($item['contents']);
            }
            if ($item['name'] === 'external_tool[editor_button][icon_url]') {
                $foundEditorButtonIcon = true;
                $this->assertEquals('https://updated.example.com/icon.png', $item['contents']);
            }
        }

        $this->assertTrue($foundCourseNavEnabled, 'Course navigation enabled should be in API array');
        $this->assertTrue($foundCourseNavText, 'Course navigation text should be in API array');
        $this->assertTrue($foundEditorButtonEnabled, 'Editor button enabled should be in API array');
        $this->assertTrue($foundEditorButtonIcon, 'Editor button icon URL should be in API array');
    }

    public function testNullValuesNotIncludedInApiArray(): void
    {
        $dto = new UpdateExternalToolDTO([
            'name' => 'Test Tool',
        ]);

        $apiArray = $dto->toApiArray();

        $nameFound = false;
        $descriptionFound = false;

        foreach ($apiArray as $item) {
            if ($item['name'] === 'external_tool[name]') {
                $nameFound = true;
            }
            if ($item['name'] === 'external_tool[description]') {
                $descriptionFound = true;
            }
        }

        $this->assertTrue($nameFound, 'Name should be included');
        $this->assertFalse($descriptionFound, 'Null description should not be included');
    }
}
