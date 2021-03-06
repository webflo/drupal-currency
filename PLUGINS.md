Currency offers the following plugin types for which plugins can be provided by
other modules:

Amount Formatter
----------------
Amount formatters convert plain numbers (e.g. `1928.37`) to human-readable
amounts/prices (e.g. €1.928,37). They are classes that implement
`\Drupal\currency\Plugin\Currency\AmountFormatter\AmountFormatterInterface` and
live in `\Drupal\$module\Plugin\Currency\AmountFormatter`, where `$module` is 
the machine name of the module that provides the plugins. The classes are 
annotated using `\Drupal\currency\Annotation\CurrencyAmountFormatter`.

Exchange rate provider
----------------------
Exchange rate providers provide exchange rates for a combination of source and
destination currencies. They are classes that implement
`\Drupal\currency\Plugin\Currency\ExchangeRateProvider\ExchangeRateProviderInterface`
and live in `\Drupal\$module\Plugin\Currency\ExchangeRateProvider`, where `$module`
is the machine name of the module that provides the plugins. The classes are
annotated using `\Drupal\currency\Annotation\CurrencyExchangeRateProvider`.
