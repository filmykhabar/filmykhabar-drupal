<?php

namespace Drupal\filmykhabar_api;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

trait HelperTrait
{
    public function getImage($fieldImage)
    {
        $returnData = [];
        $fieldImage = $fieldImage->getValue();
        if (isset($fieldImage[0]['target_id'])) {
            $file = File::load($fieldImage[0]['target_id']);
            $fileUri = $file->getFileUri();
            $returnData['url'] = file_create_url($fileUri);
            $returnData['styles'] = [
                '6x4_medium' => ImageStyle::load('6x4_medium')->buildUrl($fileUri),
                '6x4_small' => ImageStyle::load('6x4_small')->buildUrl($fileUri),
            ];

            $returnData['alt'] = isset($fieldImage[0]['alt']) ? $fieldImage[0]['alt'] : '';
            $returnData['title'] = isset($fieldImage[0]['title']) ? $fieldImage[0]['title'] : '';
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
                        '6x4_small' => ImageStyle::load('6x4_small')->buildUrl($fileUri),
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
