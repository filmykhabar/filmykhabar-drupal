<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\filmykhabar_api\NepaliCalendarTrait;
use Drupal\filmykhabar_api\HelperTrait;

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
    use NepaliCalendarTrait;
    use HelperTrait;

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

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
        $instance->entityTypeManager = $container->get('entity_type.manager');
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

        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        $responseData = [];

        // Featured contents
        $responseData['featured'] = $this->getFeaturedContents();

        // Latest contents
        $responseData['latest'] = $this->getLatestContents();

        $response = new ResourceResponse($responseData, 200);
        $response->addCacheableDependency($responseData);

        return $response;
    }

    public function getFeaturedContents()
    {
        $returnData = [];

        $sid = 'homepage_featured_content';
        $entity_subqueue = $this->entityTypeManager->getStorage('entity_subqueue')->load($sid);
        $items = $entity_subqueue->get('items')->getValue();
        if (empty($items)) {
            return [];
        }
        $nids = array_filter(array_map(function ($item) {
            if (isset($item['target_id']) && $item['target_id'] > 0) {
                return (int) $item['target_id'];
            }
        }, $items));

        if (empty($nids)) {
            return [];
        }

        $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

        foreach ($nodes as $node) {
            $data = [
                'nid' => (int) $node->id(),
                'title' => $node->getTitle(),
                'created' => (int) $node->getCreatedTime(),
                'changed' => (int) $node->getChangedTime(),
                'status' => (int) $node->isPublished(),
            ];

            $data['created_formatted'] = $this->getNepaliDateFormatted($data['created']);
            $data['changed_formatted'] = $this->getNepaliDateFormatted($data['changed']);

            // Teaser body
            $teaserBody = $node->get('field_teaser_body')->getValue();
            $teaserBody = isset($teaserBody[0]['value']) ? $teaserBody[0]['value'] : '';
            $data['teaserBody'] = $teaserBody;

            // Teaser image
            $data['teaserImage'] = $this->getMediaImage($node->get('field_media_image'));

            $returnData[] = $data;
        }

        return $returnData;
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
            foreach ($result as $row) {
                $row['created_formatted'] = $this->getNepaliDateFormatted($row['created']);
                $row['changed_formatted'] = $this->getNepaliDateFormatted($row['changed']);
                $latestContents[] = $row;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $latestContents;
    }
}
