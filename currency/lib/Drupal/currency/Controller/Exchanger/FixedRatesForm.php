<?php

/**
 * @file
 * Contains \Drupal\currency\Controller\Exchanger\FixedRatesForm.
 */

namespace Drupal\currency\Controller\Exchanger;

use Drupal\Core\ControllerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\currency\Plugin\Core\Entity\Currency;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the currency_delegator exchanger configuration form.
 */
class FixedRatesForm implements FormInterface, ControllerInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFacory;

  /**
   * A currency exchanger plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactory $configFactory, PluginManagerInterface $manager) {
    $this->configFactory = $configFactory;
    $this->manager = $manager;
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('plugin.manager.currency.exchanger'));
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'currency_exchanger_fixed_rates';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $currency_code_from = NULL, $currency_code_to = NULL) {
    $plugin = $this->manager->createInstance('currency_fixed_rates');
    $rate = $currency_code_from && $currency_code_to ? $plugin->load($currency_code_from, $currency_code_to) : NULL;

    $options = Currency::options();
    $form['currency_code_from'] = array(
      '#default_value' => isset($options[$currency_code_from]) ? $currency_code_from : 'XXX',
      '#disabled' => !is_null($rate),
      '#options' => $options,
      '#required' => TRUE,
      '#title' => t('Source currency'),
      '#type' => 'select',
    );
    $form['currency_code_to'] = array(
      '#default_value' => isset($options[$currency_code_to]) ? $currency_code_to : 'XXX',
      '#disabled' => !is_null($rate),
      '#options' => $options,
      '#required' => TRUE,
      '#title' => t('Source currency'),
      '#type' => 'select',
    );
    $form['rate'] = array(
      '#currency_code' => 'XXX',
      '#default_value' => $rate,
      '#required' => TRUE,
      '#title' => t('Conversion rate'),
      '#type' => 'currency_amount',
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['save'] = array(
      '#button_type' => 'primary',
      '#name' => 'save',
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    if (!is_null($rate)) {
      $form['actions']['delete'] = array(
        '#button_type' => 'danger',
        '#limit_validation_errors' => array(array('currency_code_from'), array('currency_code_to')),
        '#name' => 'delete',
        '#type' => 'submit',
        '#value' => t('Delete'),
      );
    }

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];
    $currency_from = entity_load('currency', $values['currency_code_from']);
    $currency_to = entity_load('currency', $values['currency_code_to']);

    switch ($form_state['triggering_element']['#name']) {
      case 'save':
        CurrencyExchangerFixedRates::save($currency_from->ISO4217Code, $currency_to->ISO4217Code, $values['rate']['amount']);
        drupal_set_message(t('The exchange rate for @currency_title_from to @currency_title_to has been saved.', array(
          '@currency_title_from' => $currency_from->translateTitle(),
          '@currency_title_to' => $currency_to->translateTitle(),
        )));
        break;
      case 'delete':
        CurrencyExchangerFixedRates::delete($currency_from->ISO4217Code, $currency_to->ISO4217Code);
        drupal_set_message(t('The exchange rate for @currency_title_from to @currency_title_to has been deleted.', array(
          '@currency_title_from' => $currency_from->translateTitle(),
          '@currency_title_to' => $currency_to->translateTitle(),
        )));
        break;
    }
    $form_state['redirect'] = 'admin/config/regional/currency-exchange/fixed';
  }
}