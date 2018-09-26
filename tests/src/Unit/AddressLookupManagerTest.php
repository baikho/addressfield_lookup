<?php

namespace Drupal\Tests\addressfield_lookup;

use Drupal\addressfield_lookup\AddressLookupManager;
use Drupal\addressfield_lookup\Exception\NoServiceAvailableException;
use Drupal\addressfield_lookup_example\Plugin\AddressLookup\Example;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

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

    // Create manager.
    $this->manager = $this->createManager();

    // Override methods.
    $this->manager->expects($this->any())
      ->method('createInstance')
      ->will($this->returnCallback([$this, 'createExamplePlugin']));

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
        'test_data' => 'TS1 1ST',
        'class' => Example::class,
        'provider' => 'addressfield_lookup_example',
      ]);
  }

  /**
   * Mocks a address lookup plugin manager.
   *
   * @return \Drupal\addressfield_lookup\AddressLookupManager;
   *   A mocked address lookup plugin manager.
   */
  public function createManager() {
    $cache = $this->prophesize(CacheBackendInterface::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $logger = $this->prophesize(LoggerInterface::class);

    $manager = $this->getMockBuilder(AddressLookupManager::class)
      ->setConstructorArgs([
        new \ArrayObject(),
        $cache->reveal(),
        $module_handler->reveal(),
      ])
      ->setMethods([
        'createInstance',
        'getDefaultId',
        'getDefinition',
        'getCacheBin',
        'getLogger',
        'getRequestTime',
        'configGet',
      ])
      ->getMock();

    $manager->expects($this->any())
      ->method('getCacheBin')
      ->willReturn($cache->reveal());

    $manager->expects($this->any())
      ->method('getLogger')
      ->willReturn($logger->reveal());

    $manager->expects($this->any())
      ->method('getRequestTime')
      ->willReturn(time());

    return $manager;
  }

  /**
   * Callback for creating an example plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   * The country to search in.
   *
   * @return object
   *   A fully configured plugin instance.
   */
  public function createExamplePlugin($plugin_id, array $configuration = [], $country = NULL) {
    return new Example($configuration, $plugin_id, [
      'id' => 'example',
      'label' => 'Example',
      'description' => 'Provides an example address field lookup service.',
      'test_data' => 'TS1 1ST',
      'class' => Example::class,
      'provider' => 'addressfield_lookup_example',
    ], $country);
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
    $addresses = $this->manager->getAddresses(static::VALID_SEARCH_TERM, static::INVALID_COUNTRY_CODE);

    // Assert that there is no result.
    $this->assertInternalType('array', $addresses);
    $this->assertEmpty($addresses);
  }

  /**
   * @covers ::getAddresses
   */
  public function testGetAddressesWithInvalidSearchTerm() {
    $this->setExpectedException(UnexpectedValueException::class);
    $this->manager->getAddresses('');
  }

  /**
   * @covers ::getAddresses
   */
  public function testGetAddressesWithNoService() {
    $manager = $this->createManager();
    $manager->expects($this->once())
      ->method('getDefaultId')
      ->willReturn(NULL);

    $this->setExpectedException(NoServiceAvailableException::class);
    $manager->getAddresses(static::VALID_SEARCH_TERM);
  }

}
