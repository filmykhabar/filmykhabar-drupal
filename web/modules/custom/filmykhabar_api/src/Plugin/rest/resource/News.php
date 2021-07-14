<?php

namespace Drupal\filmykhabar_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\filmykhabar_api\NepaliCalendarTrait;

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
                'nid' => (int) $node->id(),
                'title' => $node->getTitle(),
                'type' => $node->get('type')->getValue()[0]['target_id'],
                'status' => (int) $node->isPublished(),
                'created' => (int) $node->getCreatedTime(),
                'changed' => (int) $node->getChangedTime(),
            ];

            $teaserBody = $node->get('field_teaser_body')->getValue();
            $teaserBody = isset($teaserBody[0]['value']) ? $teaserBody[0]['value'] : "";
            $responseData['teaserBody'] = $teaserBody;

            // Teaser image
            $responseData['teaserImage'] = $this->getMediaImage($node->get('field_media_image'));

            // Body
            $body = $this->getParagraphItems($node->get('field_body')->referencedEntities());
            $responseData['body'] = $body;

            // Tags
            $tags = $this->getTags($node->get('field_tags')->referencedEntities());
            $responseData['tags'] = $tags;

            // Created date (formatted)
            $responseData['created_formatted'] = $this->getNepaliDateFormatted($responseData['created']);

            // Changed date (formatted)
            $responseData['changed_formatted'] = $this->getNepaliDateFormatted($responseData['changed']);
        }

        $response = new ResourceResponse($responseData, 200);
        $response->addCacheableDependency($responseData);

        return $response;
    }

    public function getTags($tags)
    {
        $returnData = [];
        if (!empty($tags) && is_array($tags)) {
            foreach ($tags as $tag) {
                // Tag description
                $description = $tag->get('description')->getValue();
                $description = !empty($description[0]['value']) ? $description[0]['value'] : "";

                $data = [
                    'tid' => (int) $tag->id(),
                    'name' => $tag->label(),
                    'description' => $description,
                ];

                $returnData[] = $data;
            }
        }

        return $returnData;
    }

    public function getParagraphItems($paragraphs)
    {
        $body = [];
        if (!empty($paragraphs) && is_array($paragraphs)) {
            foreach ($paragraphs as $paragraph) {
                $paragraphItem = [];
                $paragraphType = $paragraph->get('type')->getValue();
                $paragraphType = isset($paragraphType[0]['target_id']) ? $paragraphType[0]['target_id'] : "";

                switch ($paragraphType) {
                    case 'text':
                        $fieldText = $paragraph->get('field_text')->getValue();
                        $fieldText = isset($fieldText[0]['value']) ? $fieldText[0]['value'] : "";

                        $paragraphItem = [
                            'type' => $paragraphType,
                            'value' => $fieldText,
                        ];
                        break;

                    case 'image':
                        $mediaImage = $this->getMediaImage($paragraph->get('field_media_image'));
                        $paragraphItem = [
                            'type' => $paragraphType,
                            'media' => $mediaImage
                        ];
                        break;

                    case 'blockquote':
                        $fieldQuote = $paragraph->get('field_quote')->getValue();
                        $fieldQuote = isset($fieldQuote[0]['value']) ? $fieldQuote[0]['value'] : "";

                        $paragraphItem = [
                            'type' => $paragraphType,
                            'value' => $fieldQuote,
                        ];
                        break;

                    default:
                }

                if (!empty($paragraphItem)) {
                    $paragraphItem['id'] = (int) $paragraph->id();
                    $body[] = $paragraphItem;
                }
            }
        }
        return $body;
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
}
