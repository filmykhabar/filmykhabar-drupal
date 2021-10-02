<?php

namespace Drupal\filmykhabar_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'Pictures' migrate source.
 *
 * @MigrateSource(
 *   id = "pictures"
 * )
 */
class Pictures extends SqlBase
{

  /**
   * {@inheritdoc}
   */
  public function query()
  {
    $c_type = 151; // table=OptionItem, option_item_id=151, option_item_code=picture
    $parent_id = 11; // table=Category, category_id=11 (category_detail_code=picture-gallery)
    $status = 402; // table=OptionItem, option_item_id=402, option_item_code=active

    $subsubquery = $this->select('Category', 'c');
    $subsubquery
      ->fields('c', array('category_id'))
      ->condition('c.parent_id', $parent_id);

    $subquery = $this->select('Category', 'c');
    $subquery
      ->fields('c', array('category_id'))
      ->condition('c.type', $c_type)
      ->condition('c.status', $status)
      ->condition('c.parent_id', $subsubquery, 'IN');

    $query = $this->select('Picture', 'p');
    $query->join('PictureDetail', 'pd', 'pd.picture_id = p.picture_id AND pd.language_id = :language_id', array(':language_id' => 1));
    $query->join('Category', 'c', 'c.category_id = p.category_id');
    $query->join('CategoryDetail', 'cd', 'cd.category_id = p.category_id AND cd.language_id = :language_id', array(':language_id' => 1));
    $query
      ->fields('p', array('picture_id', 'category_id', 'file_name', 'file_type', 'date_created', 'date_modified'))
      ->fields('pd', array('picture_detail_code', 'photographer', 'title', 'excerpt'))
      ->fields('c', array('parent_id'))
      ->fields('cd', array('category_detail_code'))
      ->condition('p.category_id', $subquery, 'IN')
      // ->condition('p.category_id', array('66', '67', '71', '73', '75', '320', '321', '322'), 'IN')
      ->condition('p.status', $status)
      ->orderBy('p.date_created', 'ASC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields()
  {
    $fields = [
      'picture_id' => $this->t('Picture ID'),
      'category_id' => $this->t('Category ID'),
      'file_name' => $this->t('File name'),
      'file_type' => $this->t('File type'),
      'date_created' => $this->t('Date created'),
      'date_modified' => $this->t('Date modified'),
      'picture_detail_code' => $this->t('Picture detail code'),
      'title' => $this->t('Title'),
      'excerpt' => $this->t('Excerpt'),
      'photographer' => $this->t('Photographer'),
      'path_alias' => $this->t('Path alias'),
      'image' => $this->t('Image'),
      'image_destination' => $this->t('Image destination'),
      'title_decoded' => $this->t('Decoded title'),
      'parent_id' => $this->t('Parent ID'),
      'category_detail_code' => $this->t('Category detail code'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds()
  {
    return [
      'picture_id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row)
  {
    $picture_id = $row->getSourceProperty('picture_id');
    $category_detail_code = $row->getSourceProperty('category_detail_code');

    // Teaser image
    $file_name = $row->getSourceProperty('file_name');
    $file_type = $row->getSourceProperty('file_type');
    $file_name_type = md5($file_name) . '@filmykhabar.com.' . $file_type;
    $image = 'http://www.filmykhabar.com/data/picture/o/' . $file_name_type;
    $row->setSourceProperty('image', $image);

    // Teaser image destination
    $image_destination = 'public://images/' . date('Y-m', strtotime($row->getSourceProperty('date_created'))) . '/' . $file_name_type;
    $row->setSourceProperty('image_destination', $image_destination);

    // Decoded title - for image alt/title
    $title_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('title'));
    $title_decoded = html_entity_decode($title_decoded);
    $title_decoded = trim($title_decoded);
    $row->setSourceProperty('title_decoded', $title_decoded);

    // Path alias
    $path_alias = '/pictures/' . $category_detail_code . '/' . $picture_id;
    $row->setSourceProperty('path_alias', $path_alias);

    return parent::prepareRow($row);
  }
}
