<?php

namespace Drupal\migrate_filmykhabar\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a 'ContentNews' migrate source.
 *
 * @MigrateSource(
 *   id = "content_news"
 * )
 */
class ContentNews extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $fields_content = [
      'content_id',
      'category_id',
      'created_by',
      'modified_by',
      'date_display',
      'thumbnail_name',
      'thumbnail_type',
    ];

    $fields_content_detail = [
      'content_detail_code',
      'author',
      'location',
      'title',
      'excerpt',
      'body',
      'date_created',
      'date_modified',
    ];

    $query = $this->select('Content', 'c');
    $query->join('ContentDetail', 'cd', 'c.content_id = cd.content_id AND cd.language_id = :language_id', array(':language_id' => 1));
    $query
      ->fields('c', $fields_content)
      ->fields('cd', $fields_content_detail)
      ->condition('c.category_id', array(1, 44, 54, 55, 56, 57), 'IN')
      ->condition('c.status', 402)
      ->range(0, 100)
      ->orderBy('c.content_id', 'DESC');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'content_id' => $this->t('Content ID'),
      'category_id' => $this->t('Category ID'),
      'created_by' => $this->t('Created by'),
      'modified_by' => $this->t('Modified by'),
      'date_display' => $this->t('Date display'),
      'thumbnail_name' => $this->t('Thumbnail name'),
      'thumbnail_type' => $this->t('Thumbnail type'),
      'date_created' => $this->t('Date created'),
      'date_modified' => $this->t('Date modified'),
      'content_detail_code' => $this->t('Content detail code'),
      'author' => $this->t('Author'),
      'location' => $this->t('Location'),
      'title' => $this->t('Title'),
      'excerpt' => $this->t('Excerpt'),
      'body' => $this->t('Body'),
      'image' => $this->t('Promo image'),
      'image_destination' => $this->t('Promo image destination'),
      'title_decoded' => $this->t('Decoded title'),
      'news_type' => $this->t('News type'),
      'author_profile' => $this->t('Author Profile'),
      'news_location' => $this->t('News Location'),
      'path_alias' => $this->t('Path alias'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'content_id' => [
        'type' => 'integer',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $content_id = $row->getSourceProperty('content_id');

    // Teaser image
    $thumbnail_name = $row->getSourceProperty('thumbnail_name');
    $thumbnail_type = $row->getSourceProperty('thumbnail_type');
    $image = 'http://www.filmykhabar.com/data/picture/o/' . md5($thumbnail_name) . '@filmykhabar.com.' . $thumbnail_type;
    $row->setSourceProperty('image', $image);

    // Teaser image destination
    $image_destination = 'public://images/' . date('Y-m', strtotime($row->getSourceProperty('date_display'))) . '/' . $content_id . '.' . $thumbnail_type;
    $row->setSourceProperty('image_destination', $image_destination);

    // Decoded title - for image alt/title
    $title_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('title'));
    $title_decoded = html_entity_decode($title_decoded);
    $title_decoded = trim($title_decoded);
    $row->setSourceProperty('title_decoded', $title_decoded);

    // News type
    $category_id = $row->getSourceProperty('category_id');
    switch ($category_id) {
      case 44:
        // Breaking
        $news_type = 'Breaking';
        break;

      case 54:
        // Gossip
        $news_type = 'Gossip';
        break;

      case 55:
        // Featured
        $news_type = 'Featured';
        break;

      case 56:
        // Latest
        $news_type = 'Latest';
        break;

      case 57:
        // Follow-up
        $news_type = 'Follow-up';
        break;

      default:
        $news_type = '';
    }
    if (!empty($news_type)) {
      $row->setSourceProperty('news_type', $news_type);
    }

    // Author
    $author_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('author'));
    $author_decoded = html_entity_decode($author_decoded);
    $author_decoded = trim($author_decoded);
    $author = $this->getAuthorMapping($author_decoded);
    $row->setSourceProperty('author_profile', $author);

    // Location
    $location_decoded = str_replace(array('&quot;', '&#039;', '&nbsp;', "  "), array('"', "'", " ", " "), $row->getSourceProperty('location'));
    $location_decoded = html_entity_decode($location_decoded);
    $location_decoded = trim($location_decoded);
    $location = $this->getLocationMapping($location_decoded);
    $row->setSourceProperty('news_location', $location);

    // Path alias
    $path_alias = '/news/' . $content_id;
    $row->setSourceProperty('path_alias', $path_alias);

    return parent::prepareRow($row);
  }

  public function getAuthorMapping($author) {
    $authors_map = array(
      "अखण्ड भण्डारी" => array("अखण्ड भण्डारी"),
      "अनुप भट्टराई" => array("अनुप भट्टराई"),
      "अनुप भट्टराई/सुमन गैरे" => array("अनुप भट्टराई", "सुमन गैरे"),
      "अनुशील श्रेष्ठ/ऋषिराम कट्टेल" => array("अनुशील श्रेष्ठ", "ऋषिराम कट्टेल"),
      "अर्पण ढकाल" => array("अर्पण ढकाल"),
      "अर्पण ढकाल/ सुशील कार्की" => array("अर्पण ढकाल", "सुशील कार्की"),
      "अश्विनी कोइराला ( कान्तिपुर, साप्ताहिक )" => array("अश्विनी कोइराला ( कान्तिपुर, साप्ताहिक )"),
      "असिफ गुरूङ" => array("असिफ गुरूङ"),
      "उत्तम भट्टराई" => array("उत्तम भट्टराई"),
      "उत्सव रसाईली" => array("उत्सव रसाईली"),
      "एजेन्सी" => array("एजेन्सी"),
      "काठमाण्डौ" => array("फिल्मीखबर"),
      "कुशल भट्टराई" => array("कुशल भट्टराई"),
      "कुशल श्रेष्ठ" => array("कुशल श्रेष्ठ"),
      "कृष्ण न्यौपाने" => array("कृष्ण न्यौपाने"),
      "केदार बस्नेत" => array("केदार बस्नेत"),
      "गणेश ढकाल" => array("गणेश ढकाल"),
      "गिरीराज भट्टराई" => array("गिरीराज भट्टराई"),
      "गोकर्ण गौतम" => array("गोकर्ण गौतम"),
      "गोपाल कामत" => array("गोपाल कामत"),
      "गोबिन्द अर्मजा" => array("गोबिन्द अर्मजा"),
      "चर्चित ढुंगेल/ अनन्त पोखरेल" => array("चर्चित ढुंगेल", "अनन्त पोखरेल"),
      "जीवन शाही" => array("जीवन शाही"),
      "डब्बु क्षेत्री" => array("डब्बु क्षेत्री"),
      "डा. टीकाराम पोखरेल" => array("डा. टीकाराम पोखरेल"),
      "तपाईंको शुभचिन्तक" => array("फिल्मीखबर"),
      "तिर्थ संगम राई" => array("तिर्थ संगम राई"),
      "तीर्थ संगम राई" => array("तिर्थ संगम राई"),
      "त्रिलोचन कोइराला" => array("त्रिलोचन कोइराला"),
      "दयाराम घिमिरे" => array("दयाराम घिमिरे"),
      "दीपक परियार" => array("दीपक परियार"),
      "दीपेन्द्र लामा" => array("दीपेन्द्र लामा"),
      "देबेन्द्र क्षेत्री" => array("देबेन्द्र क्षेत्री"),
      "नविन थापा" => array("नविन थापा"),
      "नारायण वाग्ले" => array("नारायण वाग्ले"),
      "न्यूयोर्क" => array("फिल्मीखबर"),
      "परमानन्द पाण्डे" => array("परमानन्द पाण्डे"),
      "पुष्प लामिछाने" => array("पुष्प लामिछाने"),
      "प्रकटकुमार शिशिर" => array("प्रकटकुमार शिशिर"),
      "प्रकाश अर्याल" => array("प्रकाश अर्याल"),
      "प्रदिप कार्की" => array("प्रदिप कार्की"),
      "प्रेम लामा" => array("प्रेम लामा"),
      "फरक कोण" => array("अनुप भट्टराई"),
      "फिल्मी खबर" => array("फिल्मीखबर"),
      "फिल्मीखबर" => array("फिल्मीखबर"),
      "फिल्मीखबर डटकम" => array("फिल्मीखबर"),
      "फिल्मीखबर सम्बाददाता" => array("फिल्मीखबर"),
      "फिल्मीखबर," => array("फिल्मीखबर"),
      "बाबुराम सुवेदी" => array("बाबुराम सुवेदी"),
      "बिएच राना" => array("बिएच राना"),
      "बिकाश लामीछाने" => array("बिकाश लामीछाने"),
      "बिजय आवाज" => array("बिजय आवाज"),
      "बिरोध पोखरेल" => array("बिरोध पोखरेल"),
      "बिरोध पोखरेल/ सुशील कार्की" => array("बिरोध पोखरेल", "सुशील कार्की"),
      "बिष्णु शाही" => array("विष्णु शाही"),
      "बिष्णु सुबेदी" => array("विष्णु प्र. सुबेदी"),
      "बिष्णु सुवेदी" => array("विष्णु प्र. सुबेदी"),
      "बुटवल" => array("फिल्मीखबर"),
      "बैकुण्ठ पराजुली" => array("बैकुण्ठ पराजुली"),
      "बैकुण्ठराज पराजुली" => array("बैकुण्ठ पराजुली"),
      "भुमिराज राई" => array("भुमिराज राई"),
      "भोजराज कोइराला" => array("भोजराज कोइराला"),
      "भोजराज कोईराला" => array("भोजराज कोइराला"),
      "भोला थापा क्षेत्री" => array("भोला थापा क्षेत्री"),
      "मञ्जु गैरे" => array("मञ्जु गैरे"),
      "मणिराज गौतम" => array("मणिराज गौतम"),
      "मदन रिजाल" => array("मदन रिजाल"),
      "मनिष अन्जान" => array("मनिष अन्जान"),
      "मनिष कार्की" => array("मनिष कार्की"),
      "मनोज अधिकारी" => array("मनोज अधिकारी"),
      "महेशकुमार खाती" => array("महेशकुमार खाती"),
      "यज्ञश पण्डित" => array("यज्ञश पण्डित"),
      "युबराज निरौला/बिरोध पोखरेल" => array("युवराज निरौला", "बिरोध पोखरेल"),
      "युबराज निरौला/सुशील कार्की" => array("युवराज निरौला", "सुशील कार्की"),
      "युवराज निरौला" => array("युवराज निरौला"),
      "रमेश अधिकारी" => array("रमेश अधिकारी"),
      "राज नेपाल" => array("राज नेपाल"),
      "राजकुमार लामिछाने" => array("राजकुमार लामिछाने"),
      "राजन कठेत" => array("राजन कठेत"),
      "राजन घिमिरे" => array("राजन घिमिरे"),
      "राजाराम फुयाँल" => array("राजाराम फुयाँल"),
      "राजेश घिमिरे" => array("राजेश घिमिरे"),
      "रामचन्द्र नेपाल" => array("रामचन्द्र नेपाल"),
      "रामजी ज्ञवाली" => array("रामजी ज्ञवाली"),
      "रेम बिक" => array("रेम बिक"),
      "विजय आवाज" => array("विजय आवाज"),
      "विजय ज्ञवाली" => array("विजय ज्ञवाली"),
      "विजयरत्न तुलाधर" => array("विजयरत्न तुलाधर"),
      "विष्णु प्र. सुबेदी" => array("विष्णु प्र. सुबेदी"),
      "विष्णु शर्मा" => array("विष्णु शर्मा"),
      "विष्णु शाही" => array("विष्णु शाही"),
      "शान्ति प्रिय" => array("शान्ति प्रिय"),
      "शान्तिप्रिय" => array("शान्ति प्रिय"),
      "शिशिर चालिसे" => array("शिशिर चालिसे"),
      "शिशिर भण्डारी" => array("शिशिर भण्डारी"),
      "शेखर ढकाल" => array("शेखर ढकाल"),
      "शैलेन्द्र कठायत" => array("शैलेन्द्र कठायत"),
      "श्यामराज विक" => array("श्यामराज विक"),
      "श्यामसुन्दर गिरी" => array("श्यामसुन्दर गिरी"),
      "श्रवण देउवा" => array("श्रवण देउवा"),
      "सचिन घिमिरे" => array("सचिन घिमिरे"),
      "संजय बिश्वकर्मा" => array("संजय बिश्वकर्मा"),
      "संजय सौगात" => array("संजय सौगात"),
      "संजिब भट्ट" => array("संजिब भट्ट"),
      "सञ्जय सौगात" => array("संजय सौगात"),
      "सञ्जोग बस्याल" => array("सन्जोग बस्याल"),
      "सन्जोक बस्याल" => array("सन्जोग बस्याल"),
      "सन्जोक बस्याल /रेम बिक" => array("सन्जोग बस्याल", "रेम बिक"),
      "सन्जोग बस्याल" => array("सन्जोग बस्याल"),
      "सन्जोग बस्याल/ रेम बिक" => array("सन्जोग बस्याल", "रेम बिक"),
      "सन्जोग बस्याल/गीता राना" => array("सन्जोग बस्याल", "गीता राना"),
      "सन्जोग बस्याल/रेम बिक" => array("सन्जोग बस्याल", "रेम बिक"),
      "सन्तोष काफ्ले" => array("सन्तोष काफ्ले"),
      "सन्तोष गौतम" => array("सन्तोष गौतम"),
      "सन्तोष रिमाल" => array("सन्तोष रिमाल"),
      "सामीप्य तिमल्सेना" => array("सामीप्य तिमल्सेना"),
      "सिपी जैसी" => array("सिपी जैसी"),
      "सुमन गैरे" => array("सुमन गैरे"),
      "सुमन गैरे/ अनुप भट्टराई" => array("सुमन गैरे", "अनुप भट्टराई"),
      "सुमन वाग्ले" => array("सुमन वाग्ले"),
      "सुशील कार्की/बिरोध पोखरेल" => array("सुशील कार्की", "बिरोध पोखरेल"),
      "सुशील कार्की/युबराज निरौला" => array("सुशील कार्की", "युवराज निरौला"),
      "सोम बिक्रम सिंह" => array("सोम बिक्रम सिंह"),
    );

    if (array_key_exists($author, $authors_map)) {
      return $authors_map[$author];
    }
    else {
      return array("फिल्मीखबर");
    }
  }

  public function getLocationMapping($location) {
    $location_map = array(
      "Kathmandu" => "काठमाडौं",
      "अकल्याण्ड" => "अकल्याण्ड",
      "अमेरिका" => "अमेरिका",
      "अष्ट्रेलिया" => "अष्ट्रेलिया",
      "इजरायल" => "इजरायल",
      "इटहरी" => "इटहरी",
      "कञ्चनपुर" => "कञ्चनपुर",
      "कतार" => "कतार",
      "काठमाठौं" => "काठमाडौं",
      "काठमाडौ" => "काठमाडौं",
      "काठमाडौँ" => "काठमाडौं",
      "काठमाडौं" => "काठमाडौं",
      "काठमाडौं," => "काठमाडौं",
      "काठमाण्डौ" => "काठमाडौं",
      "काठमाण्डौ," => "काठमाडौं",
      "केन्स" => "केन्स",
      "कैलाली" => "कैलाली",
      "कोरिया" => "कोरिया",
      "क्यालिफोर्निया" => "क्यालिफोर्निया",
      "क्वालालम्पुर" => "क्वालालम्पुर",
      "चितवन" => "चितवन",
      "जनकपुर" => "जनकपुर",
      "जापान" => "जापान",
      "झापा" => "झापा",
      "टीकापुर" => "टीकापुर",
      "टेक्सास" => "टेक्सास",
      "दमक" => "दमक",
      "दमौली" => "दमौली",
      "दार्जिलिङ" => "दार्जीलिङ",
      "दार्जीलिङ" => "दार्जीलिङ",
      "दुबई" => "दुबई",
      "दोलखा" => "दोलखा",
      "दोहा" => "दोहा",
      "धरान" => "धरान",
      "नवलपरासी" => "नवलपरासी",
      "नेदरल्याण्ड" => "नेदरल्याण्ड",
      "नेपालगंज" => "नेपालगंज",
      "नेपालगंज," => "नेपालगंज",
      "नेपालगञ्ज" => "नेपालगंज",
      "न्युयोर्क" => "न्युयोर्क",
      "पोखरा" => "पोखरा",
      "प्यूठान" => "प्यूठान",
      "बनेपा" => "बनेपा",
      "बाहुनडाँगी" => "बाहुनडाँगी",
      "बिराटनगर" => "बिराटनगर",
      "बुटवल" => "बुटवल",
      "बेनी" => "बेनी",
      "बेलायत" => "बेलायत",
      "बेल्जियम" => "बेल्जियम",
      "ब्रुनाई" => "ब्रुनाई",
      "भरतपुर" => "भरतपुर",
      "मकवानपुर" => "मकवानपुर",
      "मकवानपुर,हेटौडा" => "मकवानपुर",
      "मन्थली" => "मन्थली",
      "मलेशिया" => "मलेसिया",
      "मलेसिया" => "मलेसिया",
      "महोत्तरी" => "महोत्तरी",
      "मुम्बई" => "मुम्बई",
      "मुसिकोट" => "मुसिकोट",
      "मेलर्वन" => "मेलर्वन",
      "म्याग्दी" => "म्याग्दी",
      "युएई" => "युएई",
      "लण्डन" => "लण्डन",
      "लन्डन" => "लण्डन",
      "लमजुङ" => "लमजुङ",
      "ललितपुर" => "ललितपुर",
      "वासिङटन डिसी" => "वासिङटन डिसी",
      "वेलायत" => "बेलायत",
      "सर्लाही" => "सर्लाही",
      "सानफ्रान्सिस्को" => "सानफ्रान्सिस्को",
      "सिक्किम" => "सिक्किम",
      "सिड्नी" => "सिड्नी",
      "सिन्धुली" => "सिन्धुली",
      "सिलुगुढी (भारत)" => "सिलुगुढी (भारत)",
      "सुर्खेत" => "सुर्खेत",
      "स्कटल्याण्ड" => "स्कटल्याण्ड",
      "हङकङ" => "हङकङ",
      "हेटौडा" => "हेटौडा",
      "हेटौंडा" => "हेटौडा",
    );

    if (array_key_exists($location, $location_map)) {
      return $location_map[$location];
    }
    else {
      return "";
    }
  }
}
