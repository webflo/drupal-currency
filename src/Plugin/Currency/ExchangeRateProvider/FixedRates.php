<?php

/**
 * @file
 * Contains \Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRates.
 */

namespace Drupal\currency\Plugin\Currency\ExchangeRateProvider;

use BartFeenstra\CurrencyExchange\FixedExchangeRateProviderTrait;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides manually entered exchange rates.
 *
 * @CurrencyExchangeRateProvider(
 *   id = "currency_fixed_rates",
 *   label = @Translation("Fixed rates"),
 *   operations_provider = "\Drupal\currency\Plugin\Currency\ExchangeRateProvider\FixedRatesOperationsProvider"
 * )
 */
class FixedRates extends PluginBase implements ExchangeRateProviderInterface, ContainerFactoryPluginInterface {

  use FixedExchangeRateProviderTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new class instance
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactory $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll() {
    $rates_data = $this->configFactory->get('currency.exchange_rate_provider.fixed_rates')->get('rates');
    $rates = array();
    foreach ($rates_data as $rate_data) {
      $rates[$rate_data['currency_code_from']][$rate_data['currency_code_to']] = $rate_data['rate'];
    }

    return $rates;
  }

  /**
   * Saves an exchange rate.
   *
   * @param string $source_currency_code
   * @param string $destination_currency_code
   * @param string $rate
   *
   * @return $this
   */
  public function save($source_currency_code, $destination_currency_code, $rate) {
    $config = $this->configFactory->getEditable('currency.exchange_rate_provider.fixed_rates');
    $rates = $this->loadAll();
    $rates[$source_currency_code][$destination_currency_code] = $rate;
    // Massage the rates into a format that can be stored, as associative
    // arrays are not supported by the config system
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

    $config->set('rates', $rates_data);
    $config->save();

    return $this;
  }

  /**
   * Deletes an exchange rate.
   *
   * @param string $source_currency_code
   * @param string $destination_currency_code
   *
   * @return NULL
   */
  public function delete($source_currency_code, $destination_currency_code) {
    $config = $this->configFactory->getEditable('currency.exchange_rate_provider.fixed_rates');
    $rates = $config->get('rates');
    foreach ($rates as $i => $rate) {
      if ($rate['currency_code_from'] == $source_currency_code && $rate['currency_code_to'] == $destination_currency_code) {
        unset($rates[$i]);
      }
    }
    $config->set('rates', $rates);
    $config->save();
  }
}
