<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "news_collection",
 *   label = @Translation("News collection"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/news"
 *   }
 * )
 */
class NewsCollection extends ResourceBase
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
     * Gets a list of news.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get()
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $responseData = [];

        try {
            $query = $this->database->select('node_field_data', 'nfd');
            $query
                ->condition('nfd.status', 1)
                ->fields('nfd', ['nid', 'title', 'created'])
                ->range(0, 11)
                ->orderBy('nfd.created', 'DESC');

            $responseData = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $response = new ResourceResponse($responseData, 200);
        // In order to generate fresh result every time (without clearing
        // the cache), you need to invalidate the cache.
        $response->addCacheableDependency($responseData);

        return $response;
    }
}
