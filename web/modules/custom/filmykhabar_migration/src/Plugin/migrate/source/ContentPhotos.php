<?php

namespace Drupal\filmykhabar_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Provides a 'ContentPhotos' migrate source.
 *
 * @MigrateSource(
 *   id = "content_photos"
 * )
 */
class ContentPhotos extends SqlBase
{

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    $c_type = 151;
    $parent_id = 11;
    $status = 402;

    $fields_category = [
      'category_id',
      'thumbnail_name',
      'thumbnail_type',
      'date_created',
      'date_modified',
    ];

    $fields_category_detail = [
      'category_detail_id',
      'category_detail_code',
      'title',
      'description',
    ];

    $subquery = $this->select('Category', 'c');
    $subquery
      ->fields('c', array('category_id'))
      ->condition('c.parent_id', $parent_id);

    $query = $this->select('Category', 'c');
    $query->join('CategoryDetail', 'cd', 'c.category_id = cd.category_id AND cd.language_id = :language_id', array(':language_id' => 1));
    $query
      ->fields('c', $fields_category)
      ->fields('cd', $fields_category_detail)
      ->condition('c.type', $c_type)
      ->condition('c.status', $status)
      ->condition('c.parent_id', $subquery, 'IN')
      // ->condition('c.category_id', array('66', '67', '71', '73', '75', '320', '321', '322'), 'IN')
      ->orderBy('c.date_created', 'ASC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields()
  {
    $fields = [
      'category_id' => $this->t('Category ID'),
      'thumbnail_name' => $this->t('Thumbnail name'),
      'thumbnail_type' => $this->t('Thumbnail type'),
      'date_created' => $this->t('Date created'),
      'date_modified' => $this->t('Date modified'),
      'category_detail_id' => $this->t('Content detail ID'),
      'category_detail_code' => $this->t('Content detail code'),
      'title' => $this->t('Title'),
      'description' => $this->t('Description'),
      'path_alias' => $this->t('Path alias'),
      'image' => $this->t('Promo image'),
      'image_destination' => $this->t('Promo image destination'),
      'title_decoded' => $this->t('Decoded title'),
      'picture_ids' => $this->t('Picture IDs'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds()
  {
    return [
      'category_id' => [
        'type' => 'integer',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row)
  {
    $category_id = $row->getSourceProperty('category_id');
    $category_detail_id = $row->getSourceProperty('category_detail_id');

    // Teaser image
    $thumbnail_name = $row->getSourceProperty('thumbnail_name');
    $thumbnail_type = $row->getSourceProperty('thumbnail_type');
    $image = 'http://www.filmykhabar.com/data/picture/o/' . md5($thumbnail_name) . '@filmykhabar.com.' . $thumbnail_type;
    $row->setSourceProperty('image', $image);

    // Teaser image destination
    $image_destination = 'public://images/' . date('Y-m', strtotime($row->getSourceProperty('date_created'))) . '/' . $category_id . '.' . $thumbnail_type;
    $row->setSourceProperty('image_destination', $image_destination);

    // Decoded title - for image alt/title
    $title_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('title'));
    $title_decoded = html_entity_decode($title_decoded);
    $title_decoded = trim($title_decoded);
    $row->setSourceProperty('title_decoded', $title_decoded);

    // Path alias
    $path_alias = '/pictures/' . $category_detail_id;
    $row->setSourceProperty('path_alias', $path_alias);

    // Pictures
    $query = $this->select('Picture', 'p');
    $query
      ->fields('p', array('picture_id'))
      ->condition('p.category_id', $category_id)
      ->condition('p.status', 402)
      ->orderBy('p.sequence', 'ASC');
    $result = $query->execute()->fetchAll();
    $picture_ids = [];
    foreach ($result as $val) {
      $val = (array) $val;
      $picture_ids[] = $val['picture_id'];
    }
    $row->setSourceProperty('picture_ids', $picture_ids);

    // Get paragraphs mappings.
    $drupalDb = Database::getConnection('default', 'default');
    $paragraphs = [];
    $query_mmppi = $drupalDb->select('migrate_map_pictures_paragraphs_item', 'mmppi')
      ->fields('mmppi', ['destid1', 'destid2'])
      ->condition('mmppi.sourceid1', $picture_ids, 'IN');
    $results_mmppi = $query_mmppi->execute()->fetchAll();
    if (!empty($results_mmppi)) {
      foreach ($results_mmppi as $result_mmppi) {
        $paragraphs[] = [
          'target_id' => $result_mmppi->destid1,
          'target_revision_id' => $result_mmppi->destid2,
        ];
      }
    }
    $row->setSourceProperty('field_photo_gallery', $paragraphs);

    return parent::prepareRow($row);
  }
}
