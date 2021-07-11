<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

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

            // Teaser body
            $teaserBody = $node->get('field_teaser_body')->getValue();
            $teaserBody = isset($teaserBody[0]['value']) ? $teaserBody[0]['value'] : '';
            $data['teaserBody'] = $teaserBody;

            // Teaser image
            $data['teaserImage'] = $this->getMediaImage($node->get('field_media_image'));

            // $data['extras'] = $node;

            $returnData[] = $data;
        }

        return $returnData;
    }

    public function getMediaImage($fieldMediaImage)
    {
        $returnData = [];
        $fieldMediaImage = $fieldMediaImage->first();
        if (!empty($fieldMediaImage)) {
            $fieldMediaImage = $fieldMediaImage->get('entity')->getTarget()->getValue();
            if (!empty($fieldMediaImage)) {
                // Image name
                $name = $fieldMediaImage->get('name')->getValue();
                $name = isset($name[0]['value']) ? $name[0]['value'] : '';

                // Image caption
                $caption = $fieldMediaImage->get('field_caption')->getValue();
                $caption = isset($caption[0]['value']) ? $caption[0]['value'] : '';

                // Image credit/copyright
                $creditCopyright = $fieldMediaImage->get('field_credit_copyright')->getValue();
                $creditCopyright = isset($creditCopyright[0]['value']) ? $creditCopyright[0]['value'] : '';

                // Media image
                $mediaImageUrl = "";
                $mediaImageStyles = [];
                $mediaImageAlt = "";
                $mediaImageTitle = "";
                $mediaImage = $fieldMediaImage->get('field_media_image')->getValue();
                if (isset($mediaImage[0]['target_id'])) {
                    $file = File::load($mediaImage[0]['target_id']);
                    $fileUri = $file->getFileUri();
                    $mediaImageUrl = file_create_url($fileUri);
                    $mediaImageStyles = [
                        '6x4_medium' => ImageStyle::load('6x4_medium')->buildUrl($fileUri),
                    ];
                }
                $mediaImageAlt = isset($mediaImage[0]['alt']) ? $mediaImage[0]['alt'] : '';
                $mediaImageTitle = isset($mediaImage[0]['title']) ? $mediaImage[0]['title'] : '';

                $returnData = [
                    'mid' => (int) $fieldMediaImage->id(),
                    'name' => $name,
                    'caption' => $caption,
                    'credit' => $creditCopyright,
                    'url' => $mediaImageUrl,
                    'alt' => $mediaImageAlt,
                    'title' => $mediaImageTitle,
                    'styles' => $mediaImageStyles,
                ];
            }
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
            if (!empty($result)) {
                $latestContents = $result;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $latestContents;
    }
}
