<?php

namespace Drupal\migrate_filmykhabar\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'ContentVideos' migrate source.
 *
 * @MigrateSource(
 *   id = "content_videos"
 * )
 */
class ContentVideos extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields_video = [
      'video_id',
      'category_id',
      'embed_code',
      'url',
      'thumbnail_name',
      'thumbnail_type',
      'date_created',
      'date_modified',
    ];

    $fields_video_detail = [
      'video_detail_code',
      'title',
      'excerpt',
      'body',
    ];

    $query = $this->select('Video', 'v');
    $query->join('VideoDetail', 'vd', 'v.video_id = vd.video_id AND vd.language_id = :language_id', array(':language_id' => 1));
    $query
      ->fields('v', $fields_video)
      ->fields('vd', $fields_video_detail)
      ->condition('v.category_id', array(49, 50, 51, 80, 183), 'IN')
      ->condition('v.status', 402)
      ->orderBy('v.video_id', 'ASC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'video_id' => $this->t('Video ID'),
      'category_id' => $this->t('Category ID'),
      'embed_code' => $this->t('Embed Code'),
      'url' => $this->t('URL'),
      'thumbnail_name' => $this->t('Thumbnail name'),
      'thumbnail_type' => $this->t('Thumbnail type'),
      'date_created' => $this->t('Date created'),
      'date_modified' => $this->t('Date modified'),
      'video_detail_code' => $this->t('Video detail code'),
      'title' => $this->t('Title'),
      'excerpt' => $this->t('Excerpt'),
      'body' => $this->t('Body'),
      'image' => $this->t('Promo image'),
      'image_destination' => $this->t('Promo image destination'),
      'title_decoded' => $this->t('Decoded title'),
      'video_type' => $this->t('Video type'),
      'path_alias' => $this->t('Path alias'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'video_id' => [
        'type' => 'integer',
        'alias' => 'v',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $video_id = $row->getSourceProperty('video_id');

    // Teaser image
    $thumbnail_name = $row->getSourceProperty('thumbnail_name');
    $thumbnail_type = $row->getSourceProperty('thumbnail_type');
    $image = 'http://www.filmykhabar.com/data/picture/o/' . md5($thumbnail_name) . '@filmykhabar.com.' . $thumbnail_type;
    $row->setSourceProperty('image', $image);

    // Teaser image destination
    $image_destination = 'public://images/' . date('Y-m', strtotime($row->getSourceProperty('date_display'))) . '/video-' . $video_id . '.' . $thumbnail_type;
    $row->setSourceProperty('image_destination', $image_destination);

    // Decoded title - for image alt/title
    $title_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('title'));
    $title_decoded = html_entity_decode($title_decoded);
    $title_decoded = trim($title_decoded);
    $row->setSourceProperty('title_decoded', $title_decoded);

    // Video type
    $category_detail_code = '';
    $category_id = $row->getSourceProperty('category_id');
    switch ($category_id) {
      case 49:
        $video_type = 'Movie Promo';
        $category_detail_code = 'movie-promo';
        break;

      case 50:
        $video_type = 'World Premiere';
        $category_detail_code = 'world-premiere';
        break;

      case 51:
        $video_type = 'Movie Song';
        $category_detail_code = 'movie-song';
        break;

      case 80:
        $video_type = 'Miscellaneous Videos';
        $category_detail_code = 'misc-video';
        break;

      case 183:
        $video_type = 'Extra Video';
        $category_detail_code = 'video-extra';
        break;

      default:
        $video_type = '';
    }

    if (!empty($video_type)) {
      $row->setSourceProperty('video_type', $video_type);
    }

    // Path alias
    $path_alias = '/videos/' . $category_detail_code . '/' . $video_id;
    $row->setSourceProperty('path_alias', $path_alias);

    return parent::prepareRow($row);
  }
}
