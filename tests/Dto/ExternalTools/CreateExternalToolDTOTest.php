<?php

declare(strict_types=1);

namespace Tests\Dto\ExternalTools;

use CanvasLMS\Dto\ExternalTools\CreateExternalToolDTO;
use PHPUnit\Framework\TestCase;

/**
 * Test class for CreateExternalToolDTO
 *
 * @covers \CanvasLMS\Dto\ExternalTools\CreateExternalToolDTO
 */
class CreateExternalToolDTOTest extends TestCase
{
    public static function basicExternalToolDataProvider(): array
    {
        return [
            'minimal required data' => [
                [
                    'name' => 'Test Tool',
                    'privacy_level' => 'public',
                    'consumer_key' => 'test_key',
                    'shared_secret' => 'test_secret',
                    'url' => 'https://example.com/lti/launch'
                ]
            ],
            'full configuration data' => [
                [
                    'name' => 'Full Test Tool',
                    'description' => 'A comprehensive test tool',
                    'privacy_level' => 'name_only',
                    'consumer_key' => 'full_key',
                    'shared_secret' => 'full_secret',
                    'url' => 'https://example.com/lti/launch',
                    'icon_url' => 'https://example.com/icon.png',
                    'text' => 'Click to launch',
                    'custom_fields' => ['key1' => 'value1', 'key2' => 'value2'],
                    'not_selectable' => false,
                    'oauth_compliant' => true,
                    'unified_tool_id' => 'unified123'
                ]
            ]
        ];
    }

    public static function placementConfigurationDataProvider(): array
    {
        return [
            'course navigation' => [
                'course_navigation',
                [
                    'enabled' => true,
                    'text' => 'Test Tool',
                    'visibility' => 'public',
                    'windowTarget' => '_self',
                    'default' => 'enabled'
                ]
            ],
            'editor button' => [
                'editor_button',
                [
                    'enabled' => true,
                    'icon_url' => 'https://example.com/icon.png',
                    'selection_width' => '500',
                    'selection_height' => '400',
                    'message_type' => 'ContentItemSelectionRequest'
                ]
            ],
            'resource selection' => [
                'resource_selection',
                [
                    'enabled' => true,
                    'icon_url' => 'https://example.com/resource_icon.png',
                    'selection_width' => '800',
                    'selection_height' => '600'
                ]
            ]
        ];
    }

    /**
     * @dataProvider basicExternalToolDataProvider
     */
    public function testConstructorWithValidData(array $data): void
    {
        $dto = new CreateExternalToolDTO($data);
        
        $this->assertEquals($data['name'], $dto->getName());
        $this->assertEquals($data['privacy_level'], $dto->getPrivacyLevel());
        $this->assertEquals($data['consumer_key'], $dto->getConsumerKey());
        $this->assertEquals($data['shared_secret'], $dto->getSharedSecret());
        $this->assertEquals($data['url'], $dto->getUrl());
        
        if (isset($data['description'])) {
            $this->assertEquals($data['description'], $dto->getDescription());
        }
        
        if (isset($data['custom_fields'])) {
            $this->assertEquals($data['custom_fields'], $dto->getCustomFields());
        }
    }

    public function testEmptyConstructor(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $this->assertNull($dto->getName());
        $this->assertNull($dto->getPrivacyLevel());
        $this->assertNull($dto->getConsumerKey());
        $this->assertNull($dto->getSharedSecret());
        $this->assertNull($dto->getUrl());
        $this->assertNull($dto->getDomain());
    }

    public function testBasicGettersAndSetters(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setName('Test Tool');
        $this->assertEquals('Test Tool', $dto->getName());
        
        $dto->setDescription('Test Description');
        $this->assertEquals('Test Description', $dto->getDescription());
        
        $dto->setPrivacyLevel('public');
        $this->assertEquals('public', $dto->getPrivacyLevel());
        
        $dto->setConsumerKey('test_key');
        $this->assertEquals('test_key', $dto->getConsumerKey());
        
        $dto->setSharedSecret('test_secret');
        $this->assertEquals('test_secret', $dto->getSharedSecret());
        
        $dto->setUrl('https://example.com/lti/launch');
        $this->assertEquals('https://example.com/lti/launch', $dto->getUrl());
        
        $dto->setDomain('example.com');
        $this->assertEquals('example.com', $dto->getDomain());
        
        $dto->setIconUrl('https://example.com/icon.png');
        $this->assertEquals('https://example.com/icon.png', $dto->getIconUrl());
        
        $dto->setText('Launch Tool');
        $this->assertEquals('Launch Tool', $dto->getText());
    }

    public function testCustomFieldsGettersAndSetters(): void
    {
        $dto = new CreateExternalToolDTO([]);
        $customFields = ['key1' => 'value1', 'key2' => 'value2'];
        
        $dto->setCustomFields($customFields);
        $this->assertEquals($customFields, $dto->getCustomFields());
        
        $dto->setCustomFields(null);
        $this->assertNull($dto->getCustomFields());
    }

    public function testBooleanGettersAndSetters(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setIsRceFavorite(true);
        $this->assertTrue($dto->getIsRceFavorite());
        
        $dto->setNotSelectable(false);
        $this->assertFalse($dto->getNotSelectable());
        
        $dto->setOauthCompliant(true);
        $this->assertTrue($dto->getOauthCompliant());
    }

    public function testConfigurationGettersAndSetters(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setConfigType('by_url');
        $this->assertEquals('by_url', $dto->getConfigType());
        
        $dto->setConfigXml('<xml>test</xml>');
        $this->assertEquals('<xml>test</xml>', $dto->getConfigXml());
        
        $dto->setConfigUrl('https://example.com/config.xml');
        $this->assertEquals('https://example.com/config.xml', $dto->getConfigUrl());
        
        $dto->setUnifiedToolId('unified123');
        $this->assertEquals('unified123', $dto->getUnifiedToolId());
    }

    /**
     * @dataProvider placementConfigurationDataProvider
     */
    public function testPlacementConfigurations(string $placementType, array $config): void
    {
        $dto = new CreateExternalToolDTO([]);
        $getterMethod = 'get' . ucfirst(str_replace('_', '', ucwords($placementType, '_')));
        $setterMethod = 'set' . ucfirst(str_replace('_', '', ucwords($placementType, '_')));
        
        $dto->$setterMethod($config);
        $this->assertEquals($config, $dto->$getterMethod());
    }

    public function testAccountNavigationHelperMethods(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setAccountNavigationUrl('https://example.com/account');
        $accountNav = $dto->getAccountNavigation();
        $this->assertEquals('https://example.com/account', $accountNav['url']);
        
        $dto->setAccountNavigationEnabled(true);
        $accountNav = $dto->getAccountNavigation();
        $this->assertTrue($accountNav['enabled']);
        
        $dto->setAccountNavigationText('Account Tool');
        $accountNav = $dto->getAccountNavigation();
        $this->assertEquals('Account Tool', $accountNav['text']);
    }

    public function testCourseNavigationHelperMethods(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setCourseNavigationEnabled(true);
        $courseNav = $dto->getCourseNavigation();
        $this->assertTrue($courseNav['enabled']);
        
        $dto->setCourseNavigationText('Course Tool');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('Course Tool', $courseNav['text']);
        
        $dto->setCourseNavigationVisibility('public');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('public', $courseNav['visibility']);
        
        $dto->setCourseNavigationWindowTarget('_blank');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('_blank', $courseNav['windowTarget']);
        
        $dto->setCourseNavigationDefault('enabled');
        $courseNav = $dto->getCourseNavigation();
        $this->assertEquals('enabled', $courseNav['default']);
    }

    public function testEditorButtonHelperMethods(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setEditorButtonUrl('https://example.com/editor');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('https://example.com/editor', $editorButton['url']);
        
        $dto->setEditorButtonEnabled(true);
        $editorButton = $dto->getEditorButton();
        $this->assertTrue($editorButton['enabled']);
        
        $dto->setEditorButtonIconUrl('https://example.com/icon.png');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('https://example.com/icon.png', $editorButton['icon_url']);
        
        $dto->setEditorButtonSelectionWidth('500');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('500', $editorButton['selection_width']);
        
        $dto->setEditorButtonSelectionHeight('400');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('400', $editorButton['selection_height']);
        
        $dto->setEditorButtonMessageType('ContentItemSelectionRequest');
        $editorButton = $dto->getEditorButton();
        $this->assertEquals('ContentItemSelectionRequest', $editorButton['message_type']);
    }

    public function testResourceSelectionHelperMethods(): void
    {
        $dto = new CreateExternalToolDTO([]);
        
        $dto->setResourceSelectionUrl('https://example.com/resource');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('https://example.com/resource', $resourceSelection['url']);
        
        $dto->setResourceSelectionEnabled(true);
        $resourceSelection = $dto->getResourceSelection();
        $this->assertTrue($resourceSelection['enabled']);
        
        $dto->setResourceSelectionIconUrl('https://example.com/resource_icon.png');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('https://example.com/resource_icon.png', $resourceSelection['icon_url']);
        
        $dto->setResourceSelectionWidth('800');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('800', $resourceSelection['selection_width']);
        
        $dto->setResourceSelectionHeight('600');
        $resourceSelection = $dto->getResourceSelection();
        $this->assertEquals('600', $resourceSelection['selection_height']);
    }

    public function testToApiArray(): void
    {
        $dto = new CreateExternalToolDTO([
            'name' => 'Test Tool',
            'privacy_level' => 'public',
            'consumer_key' => 'test_key',
            'shared_secret' => 'test_secret',
            'url' => 'https://example.com/lti/launch',
            'custom_fields' => ['key1' => 'value1']
        ]);
        
        $dto->setCourseNavigationEnabled(true);
        $dto->setCourseNavigationText('Test Tool');
        
        $apiArray = $dto->toApiArray();
        
        $this->assertIsArray($apiArray);
        
        foreach ($apiArray as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('contents', $item);
        }
    }

    public function testToArray(): void
    {
        $data = [
            'name' => 'Test Tool',
            'privacy_level' => 'public',
            'consumer_key' => 'test_key',
            'shared_secret' => 'test_secret',
            'url' => 'https://example.com/lti/launch'
        ];
        
        $dto = new CreateExternalToolDTO($data);
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals($data['name'], $array['name']);
        $this->assertEquals($data['privacy_level'], $array['privacyLevel']);
        $this->assertEquals($data['consumer_key'], $array['consumerKey']);
        $this->assertEquals($data['shared_secret'], $array['sharedSecret']);
        $this->assertEquals($data['url'], $array['url']);
    }

    public static function snakeCaseConversionProvider(): array
    {
        return [
            ['privacy_level', 'public', 'privacyLevel'],
            ['consumer_key', 'test_key', 'consumerKey'],
            ['shared_secret', 'test_secret', 'sharedSecret'],
            ['icon_url', 'https://example.com/icon.png', 'iconUrl'],
            ['custom_fields', ['key' => 'value'], 'customFields'],
            ['config_type', 'by_url', 'configType'],
            ['config_xml', '<xml></xml>', 'configXml'],
            ['config_url', 'https://example.com/config.xml', 'configUrl'],
            ['not_selectable', true, 'notSelectable'],
            ['oauth_compliant', false, 'oauthCompliant'],
            ['unified_tool_id', 'unified123', 'unifiedToolId'],
            ['is_rce_favorite', true, 'isRceFavorite']
        ];
    }

    /**
     * @dataProvider snakeCaseConversionProvider
     */
    public function testSnakeCaseConversion(string $snakeCase, $value, string $camelCase): void
    {
        $dto = new CreateExternalToolDTO([$snakeCase => $value]);
        $array = $dto->toArray();
        
        $this->assertArrayHasKey($camelCase, $array);
        $this->assertEquals($value, $array[$camelCase]);
    }

    public function testComplexPlacementConfiguration(): void
    {
        $courseNavConfig = [
            'enabled' => true,
            'text' => 'My Tool',
            'visibility' => 'members',
            'windowTarget' => '_blank',
            'default' => 'disabled'
        ];
        
        $editorButtonConfig = [
            'enabled' => true,
            'icon_url' => 'https://example.com/icon.png',
            'selection_width' => '500',
            'selection_height' => '400',
            'message_type' => 'ContentItemSelectionRequest'
        ];
        
        $dto = new CreateExternalToolDTO([
            'name' => 'Complex Tool',
            'privacy_level' => 'email_only',
            'consumer_key' => 'complex_key',
            'shared_secret' => 'complex_secret',
            'url' => 'https://example.com/complex',
            'course_navigation' => $courseNavConfig,
            'editor_button' => $editorButtonConfig
        ]);
        
        $this->assertEquals($courseNavConfig, $dto->getCourseNavigation());
        $this->assertEquals($editorButtonConfig, $dto->getEditorButton());
        
        $apiArray = $dto->toApiArray();
        $this->assertIsArray($apiArray);
        
        $foundCourseNav = false;
        $foundEditorButton = false;
        
        // Note: The AbstractBaseDto handles nested arrays differently than expected
        // For now, just verify that course_navigation and editor_button arrays are included
        $foundCourseNav = false;
        $foundEditorButton = false;
        
        foreach ($apiArray as $item) {
            if (str_contains($item['name'], 'course_navigation')) {
                $foundCourseNav = true;
            }
            if (str_contains($item['name'], 'editor_button')) {
                $foundEditorButton = true;
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
                $this->assertTrue($item['contents']);
            }
            if ($item['name'] === 'external_tool[course_navigation][text]') {
                $foundCourseNavText = true;
                $this->assertEquals('My Tool', $item['contents']);
            }
            if ($item['name'] === 'external_tool[editor_button][enabled]') {
                $foundEditorButtonEnabled = true;
                $this->assertTrue($item['contents']);
            }
            if ($item['name'] === 'external_tool[editor_button][icon_url]') {
                $foundEditorButtonIcon = true;
                $this->assertEquals('https://example.com/icon.png', $item['contents']);
            }
        }
        
        $this->assertTrue($foundCourseNavEnabled, 'Course navigation enabled should be in API array');
        $this->assertTrue($foundCourseNavText, 'Course navigation text should be in API array');
        $this->assertTrue($foundEditorButtonEnabled, 'Editor button enabled should be in API array');
        $this->assertTrue($foundEditorButtonIcon, 'Editor button icon URL should be in API array');
    }
}