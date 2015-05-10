<?php

/**
 * @file
 * Contains \Drupal\currency\Plugin\Filter\CurrencyExchange.
 */

namespace Drupal\currency\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface;
use Drupal\currency\InputInterface;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to exchange currencies.
 *
 * @Filter(
 *   id = "currency_exchange",
 *   module = "currency",
 *   title = @Translation("Currency exchange"),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class CurrencyExchange extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The exchange rate provider.
   *
   * @var \BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface
   */
  protected $exchangeRateProvider;

  /**
   * The input parser.
   *
   * @var \Drupal\currency\InputInterface
   */
  protected $input;

  /**
   * Constructs a new class instance
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface
   *   The string translator.
   * @param \BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface $exchange_rate_provider
   *   The exchange rate provider.
   * @param \Drupal\currency\InputInterface $input
   *   The input parser.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation, ExchangeRateProviderInterface $exchange_rate_provider, InputInterface $input) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->exchangeRateProvider = $exchange_rate_provider;
    $this->input = $input;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'), $container->get('currency.exchange_rate_provider'), $container->get('currency.input'));
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return preg_replace_callback('/\[currency:([a-z]{3}):([a-z]{3})(.*?)\]/i', [$this, 'processCallback'], $text);
  }

  /**
   * Implements preg_replace_callback() callback.
   *
   * @see self::process()
   */
  function processCallback(array $matches) {
    $source_currency_code = $matches[1];
    $destination_currency_code = $matches[2];
    $amount = str_replace(':', '', $matches[3]);
    if (strlen($amount) !== 0) {
      $amount = $this->input->parseAmount($amount);
      // The amount is invalid, so return the token.
      if (!$amount) {
        return $matches[0];
      }
    }
    // The amount defaults to 1.
    else {
      $amount = 1;
    }

    $exchange_rate = $this->exchangeRateProvider->load($source_currency_code, $destination_currency_code);
    if ($exchange_rate) {
      return bcmul($amount, $exchange_rate->getRate(), 6);
    }
    // No exchange rate could be loaded, so return the token.
    return $matches[0];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Use <code>[currency:from:to:amount]</code> to convert an amount of money from one currency to another. The <code>amount</code> parameter is optional and defaults to <code>1</code>. Example: <code>[currency:EUR:USD:100]</code>.');
  }

}
