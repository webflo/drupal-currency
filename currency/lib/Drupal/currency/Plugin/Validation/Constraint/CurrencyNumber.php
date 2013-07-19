<?php

/**
 * @file
 * Contains \Drupal\currency\Plugin\Validation\Constraint\CurrencyNumber.
 */

namespace Drupal\currency\Plugin\Validation\Constraint;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Currency number constraint.
 *
 * @Plugin(
 *   id = "CurrencyNumber",
 *   label = @Translation("Currency number"),
 *   type = { "string" }
 * )
 */
class CurrencyNumber extends Regex {

  /**
   * {@inheritdoc}
   */
  public $message = '%currency_number is not a valid ISO 4217 currency number.';

  /**
   * {@inheritdoc}
   */
  public $pattern = '/^[\d]{3}$/i';
}