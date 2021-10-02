<?php

namespace Drupal\filmykhabar_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Decode HTML entities to utf-8.
 *
 * @MigrateProcessPlugin(
 *   id = "decode_content"
 * )
 *
 * To decode HTML entities to utf-8 use the following:
 *
 * @code
 * field_text:
 *   plugin: decode_content
 *   source: key-of-text-field
 * @endcode
 *
 */
class DecodeContent extends ProcessPluginBase
{

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
  {
    $value = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $value);
    $value = html_entity_decode($value);
    $value = str_replace(array('<p> </p>', '<div> </div>'), array("", ""), $value);
    $value = trim($value);
    return $value;
  }
}
