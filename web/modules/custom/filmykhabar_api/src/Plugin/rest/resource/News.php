<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "news",
 *   label = @Translation("News"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/news/{nid}"
 *   }
 * )
 */
class News extends ResourceBase
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
        $instance->entityTypeManager = $container->get('entity_type.manager');
        return $instance;
    }

    /**
     * Responds to GET requests.
     *
     * @param string $nid
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get($nid)
    {

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $responseData = [];

        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        if (!empty($node)) {
            $responseData = [
                'nid' => $node->id(),
                'title' => $node->getTitle(),
                'type' => $node->get('type')->getValue()[0]['target_id'],
                'uuid' => $node->get('uuid')->getValue()[0]['value'],
                'status' => $node->isPublished(),
                'teaser_body' => $node->get('field_teaser_body')->getValue()[0]['value'],
            ];

            // Teaser image

            // Body
        }

        // return new ResourceResponse($responseData, 200);

        $response = new ResourceResponse($responseData, 200);
        // In order to generate fresh result every time (without clearing
        // the cache), you need to invalidate the cache.
        $response->addCacheableDependency($responseData);

        return $response;
    }
}
