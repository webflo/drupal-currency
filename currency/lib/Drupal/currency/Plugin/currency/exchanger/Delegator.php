<?php

/**
 * @file
 * Contains \Drupal\currency\Plugin\currency\exchanger\Delegator.
 */

namespace Drupal\currency\Plugin\currency\exchanger;

use Drupal\Component\Annotation\Plugin;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\currency\Exchanger\ExchangerInterface;

/**
 * A currency exchanger that delegates its tasks to all other exchangers.
 *
 * @Plugin(
 *   id = "currency_delegator",
 *   label = @Translation("All exchangers"),
 *   operations = {
 *     "admin/config/regional/currency-exchange" = @Translation("configure"),
 *   }
 * )
 */
class Delegator extends PluginBase implements ExchangerInterface {

  /**
   * Loads the configuration.
   *
   * @return array
   *   Keys are currency_exchanger plugin names. Values are booleans that
   *   describe whether the plugins are enabled. Items are ordered by weight.
   */
  public function loadConfiguration() {
    // @todo Use dependency injection when http://drupal.org/node/1863816 is
    // fixed.
    $manager = drupal_container()->get('plugin.manager.currency.exchanger');
    $definitions = $manager->getDefinitions();
    $configuration = config('currency.exchanger.delegator')->get('exchangers') + array_fill_keys(array_keys($definitions), TRUE);
    // Skip this plugin, because it can never delegate to itself. It should
    // never be part of the configuration anyway. This unset() is just a
    // fail-safe.
    unset($configuration['currency_delegator']);

    return $configuration;
  }

  /**
   * Saves the configuration.
   *
   * @param array $configuration
   *   Keys are currency_exchanger plugin names. Values are booleans that
   *   describe whether the plugins are enabled. Items are ordered by weight.
   *
   * @return NULL
   */
  public function saveConfiguration(array $configuration) {
    $config = config('currency.exchanger.delegator');
    $config->set('exchangers', $configuration);
    $config->save();
  }

  /**
   * Returns enabled currency exchanger plugins, sorted by weight.
   *
   * @return array
   */
  public function loadExchangers() {
    // @todo Use dependency injection when http://drupal.org/node/1863816 is
    // fixed.
    $manager = drupal_container()->get('plugin.manager.currency.exchanger');
    $names = array_keys(array_filter($this->loadConfiguration()));
    $plugins = array();
    foreach ($names as $name) {
      $plugins[$name] = $manager->createInstance($name);
    }

    return $plugins;
  }

  /**
   * Implements \Drupal\currency\Exchanger\ExchangerInterface::load().
   */
  public function load($currency_code_from, $currency_code_to) {
    if ($currency_code_from == $currency_code_to) {
      return 1;
    }
    foreach ($this->loadExchangers() as $exchanger) {
      if ($rate = $exchanger->load($currency_code_from, $currency_code_to)) {
        return $rate;
      }
    }
    return FALSE;
  }

  /**
   * Implements \Drupal\currency\Exchanger\ExchangerInterface::loadMultiple().
   */
  public function loadMultiple(array $currency_codes) {
    $rates = array();

    // Set rates for identical source and destination currencies.
    foreach ($currency_codes as $currency_code_from => $currency_codes_to) {
      foreach ($currency_codes_to as $index => $currency_code_to) {
        if ($currency_code_from == $currency_code_to) {
          $rates[$currency_code_from][$currency_code_to] = 1;
          unset($currency_codes[$currency_code_from][$index]);
        }
      }
    }

    foreach ($this->loadExchangers() as $exchanger) {
      foreach ($exchanger->loadMultiple($currency_codes) as $currency_code_from => $currency_codes_to) {
        foreach ($currency_codes_to as $currency_code_to => $rate) {
          $rates[$currency_code_from][$currency_code_to] = $rate;
          // If we found a rate, prevent it from being looked up by the next exchanger.
          if ($rate) {
            $index = array_search($currency_code_to, $currency_codes[$currency_code_from]);
            unset($currency_codes[$currency_code_from][$index]);
          }
        }
      }
    }

    return $rates;
  }
}