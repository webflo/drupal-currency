<?php

/**
 * @file Contains
 * \Drupal\Tests\currency\Unit\Plugin\Currency\ExchangeRateProvider\FixedRatesUnitTest.
 */

namespace Drupal\Tests\currency\Unit\Plugin\Currency\ExchangeRateProvider;

use Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates
 *
 * @group Currency
 */
class FixedRatesUnitTest extends UnitTestCase {

  /**
   * The config used for testing.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $config;

  /**
   * The config factory used for testing.
   *
   * @var \Drupal\Core\Config\ConfigFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The plugin under test.
   *
   * @var \Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $configuration = array();
    $plugin_id = $this->randomMachineName();
    $plugin_definition = array();

    $this->configFactory = $this->getMockBuilder('\Drupal\Core\Config\ConfigFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->plugin = new FixedRates($configuration, $plugin_id, $plugin_definition, $this->configFactory);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $map = array(
      array('config.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->configFactory),
    );
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map));

    $form = FixedRates::create($container, array(), '', array());
    $this->assertInstanceOf('\Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates', $form);
  }

  /**
   * @covers ::loadAll
   */
  public function testLoadAll() {
    list($rates) = $this->prepareExchangeRates();
    $this->assertSame($rates, $this->plugin->loadAll());
  }

  /**
   * @covers ::save
   * @covers ::load
   */
  public function testLoad() {
    list($rates) = $this->prepareExchangeRates();
    $reverse_rate = '0.511291';

    // Test rates that are stored in config.
    $this->assertSame($rates['EUR']['NLG'], $this->plugin->load('EUR', 'NLG')->getRate());
    $this->assertSame($rates['NLG']['EUR'], $this->plugin->load('NLG', 'EUR')->getRate());
    $this->assertSame($rates['EUR']['DEM'], $this->plugin->load('EUR', 'DEM')->getRate());

    // Test a rate that is calculated on-the-fly.
    $this->assertSame($reverse_rate, $this->plugin->load('DEM', 'EUR')->getRate());

    // Test an unavailable exchange rate.
    $this->assertNull($this->plugin->load('NLG', 'UAH'));
  }

  /**
   * @covers ::loadMultiple
   */
  public function testLoadMultiple() {
    list($rates) = $this->prepareExchangeRates();

    $rates = array(
      'EUR' => array(
        'NLG' => $rates['EUR']['NLG'],
      ),
      'NLG' => array(
        'EUR' => $rates['NLG']['EUR'],
      ),
      'ABC' => array(
        'XXX' => NULL,
      ),
    );

    $returned_rates = $this->plugin->loadMultiple(array(
      // Test a rate that is stored in config.
      'EUR' => array('NLG'),
      // Test a reverse exchange rate.
      'NLG' => array('EUR'),
      // Test an unavailable exchange rate.
      'ABC' => array('XXX'),
    ));
    $this->assertSame($rates['EUR']['NLG'], $returned_rates['EUR']['NLG']->getRate());
    $this->assertSame($rates['NLG']['EUR'], $returned_rates['NLG']['EUR']->getRate());
    $this->assertNull($returned_rates['ABC']['XXX']);
  }

  /**
   * @covers ::save
   */
  public function testSave() {
    $source_currency_code = $this->randomMachineName(3);
    $destination_currency_code = $this->randomMachineName(3);
    $rate = mt_rand();
    list($rates, $rates_data) = $this->prepareExchangeRates();
    $rates[$source_currency_code][$destination_currency_code] = $rate;
    $rates_data[] = array(
      'currency_code_from' => $source_currency_code,
      'currency_code_to' => $destination_currency_code,
      'rate' => $rate,
    );

    $this->config->expects($this->once())
      ->method('set')
      ->with('rates', $rates_data);
    $this->config->expects($this->once())
      ->method('save');

    $this->plugin->save($source_currency_code, $destination_currency_code, $rate);
  }

  /**
   * @covers ::delete
   */
  function testDelete() {
    list($rates, $rates_data) = $this->prepareExchangeRates();
    unset($rates['EUR']['NLG']);
    unset($rates_data[1]);

    $this->config->expects($this->once())
      ->method('set')
      ->with('rates', $rates_data);
    $this->config->expects($this->once())
      ->method('save');

    $this->plugin->delete('EUR', 'NLG');
  }

  /**
   * Stores random exchange rates in the mocked config and returns them.
   *
   * @return array
   *   An array of the same format as the return value of
   *   \Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates::loadAll().
   */
  protected function prepareExchangeRates() {
    $rates = array(
      'EUR' => array(
        'DEM' => '1.95583',
        'NLG' => '2.20371',
      ),
      'NLG' => array(
        'EUR' => '0.453780216',
      ),
    );
    $rates_data = array();
    foreach ($rates as $source_currency_code => $source_currency_code_rates) {
      foreach ($source_currency_code_rates as $destination_currency_code => $rate) {
        $rates_data[] = array(
          'currency_code_from' => $source_currency_code,
          'currency_code_to' => $destination_currency_code,
          'rate' => $rate,
        );
      }
    }

    $this->config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $this->config->expects($this->any())
      ->method('get')
      ->with('rates')
      ->will($this->returnValue($rates_data));

    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('currency.exchange_rate_provider.fixed_rates')
      ->will($this->returnValue($this->config));
    $this->configFactory->expects($this->any())
      ->method('getEditable')
      ->with('currency.exchange_rate_provider.fixed_rates')
      ->will($this->returnValue($this->config));

    return array($rates, $rates_data);
  }

}
