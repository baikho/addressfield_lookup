<?php

namespace Drupal\Tests\addressfield_lookup;

use Drupal\addressfield_lookup\AddressLookupManager;
use Drupal\addressfield_lookup_example\Plugin\AddressLookup\Example;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\addressfield_lookup\AddressLookupManager
 * @group addressfield_lookup
 */
class AddressLookupManagerTest extends UnitTestCase {

  /**
   * A valid test search term to use for address lookups.
   *
   * @var string
   */
  const VALID_SEARCH_TERM = 'TS1 1ST';

  /**
   * An invalid test search term to use for address lookups.
   *
   * @var string
   */
  const INVALID_SEARCH_TERM = 'FK4 4KE';

  /**
   * An invalid country code to use for address lookups.
   *
   * @var string
   */
  const INVALID_COUNTRY_CODE = 'XX';

  /**
   * The address lookup plugin manager.
   *
   * @var \Drupal\addressfield_lookup\AddressLookupManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $cache = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $logger = $this->prophesize(LoggerInterface::class);

    // Create manager.
    $this->manager = $this->getMockBuilder(AddressLookupManager::class)
      ->setConstructorArgs([
        new \ArrayObject(),
        $cache->reveal(),
        $module_handler->reveal(),
      ])
      ->setMethods([
        'getDefaultId',
        'getDefinition',
        'getCacheBin',
        'getLogger',
        'getRequestTime',
        'configGet',
      ])
      ->getMock();

    // Set factory.
    $factory = $this->prophesize(FactoryInterface::class);
    $factory->createInstance('example', ['country' => NULL])->willReturn(new Example());
    $reflection_property = new \ReflectionProperty($this->manager, 'factory');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($this->manager, $factory->reveal());

    // Override methods.
    $this->manager->expects($this->any())
      ->method('getDefaultId')
      ->willReturn('example');

    $this->manager->expects($this->any())
      ->method('getDefinition')
      ->with('example')
      ->willReturn([
        'id' => 'example',
        'label' => 'Example',
        'description' => 'Provides an example address field lookup service.',
        'factory' => 'addressfield_lookup_example_create',
        'test_data' => 'TS1 1ST',
        'class' => Example::class,
        'provider' => 'addressfield_lookup_example',
      ]);

    $this->manager->expects($this->any())
      ->method('getCacheBin')
      ->willReturn($cache->reveal());

    $this->manager->expects($this->any())
      ->method('getLogger')
      ->willReturn($logger->reveal());

    $this->manager->expects($this->any())
      ->method('getRequestTime')
      ->willReturn(time());
  }

  /**
   * Get a list of array keys expected in an address lookup result.
   *
   * @return array
   *   Array of array keys.
   */
  protected function getAddressResultKeys() {
    return [
      'id',
      'street',
      'place',
    ];
  }

  /**
   * @covers ::getAddresses
   */
  public function testGetAddresses() {
    $addresses = $this->manager->getAddresses(static::VALID_SEARCH_TERM);

    // Assert that there is a result.
    $this->assertInternalType('array', $addresses);
    $this->assertNotEmpty($addresses);

    // Test the format of the result.
    foreach ($addresses as $address) {
      foreach ($this->getAddressResultKeys() as $key) {
        $this->assertTrue(isset($address[$key]) && !empty($address[$key]));
      }
    }

    // Test with an invalid search term.
    $addresses = $this->manager->getAddresses(static::INVALID_SEARCH_TERM);

    // Assert that there is no result.
    $this->assertInternalType('array', $addresses);
    $this->assertEmpty($addresses);

    // Test with an invalid country code.
    $this->markTestIncomplete('The country code is not passed to plugins yet.');
    $addresses = $this->manager->getAddresses(static::VALID_SEARCH_TERM, static::INVALID_COUNTRY_CODE);

    // Assert that there is no result.
    $this->assertInternalType('array', $addresses);
    $this->assertEmpty($addresses);
  }

}
