<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\filmykhabar_api\NepaliCalendarTrait;

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
    use NepaliCalendarTrait;

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

        $newsList = [];

        $page = (!empty($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? floor($_GET['page']) : 0;
        $limit = (!empty($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0) ? floor($_GET['limit']) : 10;
        $start = $page * $limit;

        try {
            $query = $this->getNewsListQuery();
            $query
                ->fields('nfd', ['nid', 'title', 'created', 'changed'])
                ->orderBy('nfd.created', 'DESC')
                ->range($start, $limit);
            $query->addField('nftb', 'field_teaser_body_value', 'teaserBody');

            $result = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                $row['created_formatted'] = $this->getNepaliDateFormatted($row['created']);
                $row['changed_formatted'] = $this->getNepaliDateFormatted($row['changed']);
                $newsList[] = $row;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $responseData = [
            'count' => $this->getNewsCount(),
            'data' => $newsList
        ];

        $response = new ResourceResponse($responseData, 200);
        // In order to generate fresh result every time (without clearing
        // the cache), you need to invalidate the cache.
        $response->addCacheableDependency($responseData);

        return $response;
    }

    public function getNewsListQuery()
    {
        $query = $this->database->select('node_field_data', 'nfd');
        $query->join('node__field_teaser_body', 'nftb', 'nftb.entity_id = nfd.nid');
        $query
            ->condition('nfd.type', 'news')
            ->condition('nfd.status', 1)
            ->condition('nfd.langcode', 'ne');
        return $query;
    }

    public function getNewsCount()
    {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $count = 0;

        try {
            $query = $this->getNewsListQuery();
            $count = $query->countQuery()->execute()->fetchField();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return (int) $count;
    }
}
