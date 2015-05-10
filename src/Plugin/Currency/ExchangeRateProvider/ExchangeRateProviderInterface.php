<?php

/**
 * @file Contains
 * \Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderInterface.
 */

namespace Drupal\currency\Plugin\Currency\ExchangeRateProvider;

use Drupal\Component\Plugin\PluginInspectionInterface;
use BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface as GenericExchangeRateProviderInterface;

/**
 * Defines a currency exchange rate provider plugin.
 */
interface ExchangeRateProviderInterface extends GenericExchangeRateProviderInterface, PluginInspectionInterface {
}
