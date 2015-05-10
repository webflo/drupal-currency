<?php

/**
 * @file
 * Contains \Drupal\Tests\currency\Unit\Plugin\Filter\CurrencyExchangeUnitTest.
 */

namespace Drupal\Tests\currency\Unit\Plugin\Filter;

use BartFeenstra\CurrencyExchange\ExchangeRate;
use Drupal\currency\Plugin\Filter\CurrencyExchange;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\currency\Plugin\Filter\CurrencyExchange
 *
 * @group Currency
 */
class CurrencyExchangeUnitTest extends UnitTestCase {

  /**
   * The exchange rate provider used for testing.
   *
   * @var \BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $exchangeRateProvider;

  /**
   * The filter under test.
   *
   * @var \Drupal\currency\Plugin\Filter\CurrencyExchange|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $filter;

  /**
   * The input parser used for testing.
   *
   * @var \Drupal\currency\InputInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $input;

  /**
   * The plugin definiton.
   *
   * @var mixed[]
   */
  protected $pluginDefinition;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $this->pluginDefinition = [
      'cache' => TRUE,
      'provider' => $this->randomMachineName(),
    ];

    $this->exchangeRateProvider = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');

    $this->input = $this->getMock('\Drupal\currency\InputInterface');

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->filter = new CurrencyExchange($configuration, $plugin_id, $this->pluginDefinition, $this->stringTranslation, $this->exchangeRateProvider, $this->input);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
    $map = [
      ['currency.input', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->input],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
      ['currency.exchange_rate_provider', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->exchangeRateProvider],
    ];
    $container->expects($this->any())
      ->method('get')
      ->will($this->returnValueMap($map));

    $filter = CurrencyExchange::create($container, [], '', $this->pluginDefinition);
    $this->assertInstanceOf('\Drupal\currency\Plugin\Filter\CurrencyExchange', $filter);
  }

  /**
   * @covers ::process
   * @covers ::processCallback
   */
  public function testProcess() {
    $source_currency_code = 'EUR';
    $destination_currency_code = 'NLG';
    $rate = '2.20371';
    $exchange_rate = ExchangeRate::create($source_currency_code, $destination_currency_code, $rate);

    $this->input->expects($this->any())
      ->method('parseAmount')
      ->will($this->returnArgument(0));

    $this->exchangeRateProvider->expects($this->any())
      ->method('load')
      ->with($source_currency_code, $destination_currency_code)
      ->will($this->returnValue($exchange_rate));

    $langcode = $this->randomMachineName(2);
    $cache = TRUE;
    $cache_id = $this->randomMachineName();

    $tokens_valid = [
      '[currency:EUR:NLG]' => '2.20371',
      '[currency:EUR:NLG:1]' => '2.20371',
      '[currency:EUR:NLG:2]' => '4.40742',
    ];
    $tokens_invalid = [
      // Missing arguments.
      '[currency]',
      '[currency:]',
      '[currency::]',
      '[currency:EUR]',
      // Invalid currency code.
      '[currency:EUR:123]',
      '[currency:123:EUR]',
      // Invalid currency code and missing argument.
      '[currency:123]',
    ];

    foreach ($tokens_valid as $token => $replacement) {
      $this->assertSame($replacement, $this->filter->process($token, $langcode, $cache, $cache_id));
    }
    foreach ($tokens_invalid as $token) {
      $this->assertSame($token, $this->filter->process($token, $langcode, $cache, $cache_id));
    }
  }

  /**
   * @covers ::tips
   */
  public function testTips() {
    $this->assertInternalType('string', $this->filter->tips());
  }
}
