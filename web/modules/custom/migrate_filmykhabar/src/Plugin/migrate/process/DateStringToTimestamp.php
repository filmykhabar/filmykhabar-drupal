<?php

namespace Drupal\migrate_filmykhabar\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Convert human readable date string to unix timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "date_string_to_timestamp"
 * )
 *
 * To convert human readable date string to unix timestamp use the following:
 *
 * @code
 * created:
 *   plugin: date_string_to_timestamp
 *   source: date_created
 * @endcode
 *
 */
class DateStringToTimestamp extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return strtotime($value);
  }
}
