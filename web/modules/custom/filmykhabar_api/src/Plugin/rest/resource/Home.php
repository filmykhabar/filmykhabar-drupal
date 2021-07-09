<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
// use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "home",
 *   label = @Translation("Home"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/home"
 *   }
 * )
 */
class Home extends ResourceBase
{

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The database connection.
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('filmykhabar_api');
    $instance->currentUser = $container->get('current_user');
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get()
  {

    // // You must to implement the logic of your REST Resource here.
    // // Use current user after pass authentication to validate access.
    // if (!$this->currentUser->hasPermission('access content')) {
    //   throw new AccessDeniedHttpException();
    // }

    $responseData = [];

    // Featured contetns
    $responseData['featured'] = $this->getFeaturedContents();

    // Latest contents
    $responseData['latest'] = $this->getLatestContents();

    $response = new ResourceResponse($responseData, 200);
    $response->addCacheableDependency($responseData);

    return $response;
  }

  public function getFeaturedContents()
  {
    $featuredContents = [];

    return $featuredContents;
  }

  public function getLatestContents()
  {
    $latestContents = [];
    try {
      $query = $this->database->select('node_field_data', 'nfd');
      $query->join('node__field_teaser_body', 'nftb', 'nftb.entity_id = nfd.nid');
      $query
        ->condition('nfd.status', 1)
        ->fields('nfd', ['nid', 'title', 'created', 'changed'])
        ->range(0, 11)
        ->orderBy('nfd.created', 'DESC');
      $query->addField('nftb', 'field_teaser_body_value', 'teaserBody');

      $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
      if (!empty($result)) {
        $latestContents = $result;
      }
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }

    return $latestContents;
  }
}
