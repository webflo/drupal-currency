<?php

/**
 * @file Contains \Drupal\Tests\currency\Unit\PluginBasedExchangeRateProviderUnitTest.
 */

namespace Drupal\Tests\currency\Unit;

use BartFeenstra\CurrencyExchange\ExchangeRate;
use Drupal\currency\PluginBasedExchangeRateProvider;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\currency\PluginBasedExchangeRateProvider
 *
 * @group Currency
 */
class PluginBasedExchangeRateProviderUnitTest extends UnitTestCase {

  /**
   * The configuration factory used for testing.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The class under test.
   *
   * @var \Drupal\currency\PluginBasedExchangeRateProvider
   */
  protected $sut;

  /**
   * The currency exchange rate provider plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currencyExchangeRateProviderManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->configFactory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');

    $this->currencyExchangeRateProviderManager = $this->getMock('\Drupal\Component\Plugin\PluginManagerInterface');

    $this->sut = new PluginBasedExchangeRateProvider($this->currencyExchangeRateProviderManager, $this->configFactory);
  }

  /**
   * @covers ::__construct
   */
  public function testConstruct() {
    $this->sut = new PluginBasedExchangeRateProvider($this->currencyExchangeRateProviderManager, $this->configFactory);
  }

  /**
   * @covers ::loadConfiguration
   */
  public function testLoadConfiguration() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_id_b = $this->randomMachineName();

    $plugin_definitions = array(
      $plugin_id_a => array(),
      $plugin_id_b => array(),
    );

    $config_value = array(
      array(
        'plugin_id' => $plugin_id_b,
        'status' => TRUE,
      ),
    );

    $this->currencyExchangeRateProviderManager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue($plugin_definitions));

    $config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('plugins')
      ->will($this->returnValue($config_value));

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('currency.exchange_rate_provider')
      ->will($this->returnValue($config));

    $configuration = $this->sut->loadConfiguration();
    $expected = array(
      $plugin_id_b => TRUE,
      $plugin_id_a => FALSE,
    );
    $this->assertSame($expected, $configuration);
  }

  /**
   * @covers ::saveConfiguration
   */
  public function testSaveConfiguration() {
    $configuration = array(
      'currency_historical_rates' => TRUE,
      'currency_fixed_rates' => TRUE,
      'foo' => FALSE,
    );
    $configuration_data = array(
      array(
        'plugin_id' => 'currency_historical_rates',
        'status' => TRUE,
      ),
      array(
        'plugin_id' => 'currency_fixed_rates',
        'status' => TRUE,
      ),
      array(
        'plugin_id' => 'foo',
        'status' => FALSE,
      ),
    );

    $config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('set')
      ->with('plugins', $configuration_data);
    $config->expects($this->once())
      ->method('save');

    $this->configFactory->expects($this->once())
      ->method('getEditable')
      ->with('currency.exchange_rate_provider')
      ->will($this->returnValue($config));

    $this->sut->saveConfiguration($configuration);
  }

  /**
   * @covers ::load
   * @covers ::getExchangeRateProviders
   */
  public function testLoad() {
    $source_currency_code = 'EUR';
    $destination_currency_code = 'NLG';
    $rate = ExchangeRate::create($source_currency_code, $destination_currency_code, '2.20371');

    $exchange_rate_provider_id_a = $this->randomMachineName();

    $exchange_rate_provider_id_b = $this->randomMachineName();
    $exchange_rate_provider_b = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
    $exchange_rate_provider_b->expects($this->once())
      ->method('load')
      ->with($source_currency_code, $destination_currency_code)
      ->willReturn($rate);

    $plugin_definitions = [
      $exchange_rate_provider_id_a => [
        'id' => $exchange_rate_provider_id_a,
      ],
      $exchange_rate_provider_id_b => [
        'id' => $exchange_rate_provider_id_b,
      ],
    ];
    $this->currencyExchangeRateProviderManager->expects($this->once())
      ->method('createInstance')
      ->with($exchange_rate_provider_id_b)
      ->willReturn($exchange_rate_provider_b);
    $this->currencyExchangeRateProviderManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($plugin_definitions);

    $config_value = [
      [
        'plugin_id' => $exchange_rate_provider_id_a,
        'status' => FALSE,
      ],
      [
        'plugin_id' => $exchange_rate_provider_id_b,
        'status' => TRUE,
      ],
    ];
    $config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->once())
      ->method('get')
      ->with('plugins')
      ->will($this->returnValue($config_value));

    $this->configFactory->expects($this->once())
      ->method('get')
      ->with('currency.exchange_rate_provider')
      ->will($this->returnValue($config));

    $this->assertSame($rate, $this->sut->load($source_currency_code, $destination_currency_code));
  }

}
