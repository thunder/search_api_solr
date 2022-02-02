<?php

namespace Drupal\search_api_solr\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\search_api_solr\Utility\Utility as SearchApiSolrUtility;
use Drupal\search_api_solr\SolrFieldTypeInterface;

/**
 * Defines the SolrFieldType entity.
 *
 * @ConfigEntityType(
 *   id = "solr_field_type",
 *   label = @Translation("Solr Field Type"),
 *   handlers = {
 *     "list_builder" = "Drupal\search_api_solr\Controller\SolrFieldTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\search_api_solr\Form\SolrFieldTypeForm",
 *       "edit" = "Drupal\search_api_solr\Form\SolrFieldTypeForm",
 *       "delete" = "Drupal\search_api_solr\Form\SolrFieldTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "solr_field_type",
 *   admin_permission = "administer search_api",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "disabled" = "disabled_field_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "minimumSolrVersion" = "minimum_solr_version",
 *     "customCode" = "custom_code",
 *     "fieldTypeLanguageCode" = "field_type_language_code",
 *     "domains",
 *     "fieldType" = "field_type",
 *     "unstemmedFieldType" = "unstemmed_field_type",
 *     "spellcheckFieldType" = "spellcheck_field_type",
 *     "collatedFieldType" = "collated_field_type",
 *     "solrConfigs" = "solr_configs",
 *     "textFiles" = "text_files"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/search-api/solr_field_type/{solr_field_type}",
 *     "delete-form" = "/admin/config/search/search-api/solr_field_type/{solr_field_type}/delete",
 *     "disable-for-server" = "/admin/config/search/search-api/server/{search_api_server}/solr_field_type/{solr_field_type}/disable",
 *     "enable-for-server" = "/admin/config/search/search-api/server/{search_api_server}/solr_field_type/{solr_field_type}/enable",
 *     "collection" = "/admin/config/search/search-api/server/{search_api_server}/solr_field_type"
 *   }
 * )
 */
class SolrFieldType extends AbstractSolrEntity implements SolrFieldTypeInterface {

  /**
   * Solr Field Type definition.
   *
   * @var array
   */
  protected $fieldType;

  /**
   * Solr Spellcheck Field Type definition.
   *
   * @var array
   */
  protected $spellcheckFieldType;

  /**
   * Solr Collated Field Type definition.
   *
   * @var array
   */
  protected $collatedFieldType;

  /**
   * Solr Unstemmed Field Type definition.
   *
   * @var array
   */
  protected $unstemmedFieldType;

  /**
   * The custom code targeted by this Solr Field Type.
   *
   * @var string
   */
  protected $customCode;

  /**
   * The language targeted by this Solr Field Type.
   *
   * @var string
   */
  protected $fieldTypeLanguageCode;

  /**
   * The targeted content domains.
   *
   * @var string[]
   */
  protected $domains;

  /**
   * {@inheritdoc}
   */
  public function getFieldType() {
    return $this->fieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldType(array $field_type) {
    $this->fieldType = $field_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->fieldType['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSpellcheckFieldType() {
    return $this->spellcheckFieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function setSpellcheckFieldType(array $spellcheck_field_type) {
    $this->spellcheckFieldType = $spellcheck_field_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollatedFieldType() {
    return $this->collatedFieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function setCollatedFieldType(array $collated_field_type) {
    $this->collatedFieldType = $collated_field_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnstemmedFieldType() {
    return $this->unstemmedFieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnstemmedFieldType(array $unstemmed_field_type) {
    $this->unstemmedFieldType = $unstemmed_field_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomCode() {
    return $this->customCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeLanguageCode() {
    return $this->fieldTypeLanguageCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomains() {
    return empty($this->domains) ? ['generic'] : $this->domains;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->getDomains();
  }

  /**
   * Get all available domains form solr filed type configs.
   *
   * @return string[]
   *   An array of domains as strings.
   */
  public static function getAvailableDomains() {
    return parent::getAvailableOptions('domains', 'generic', 'search_api_solr.solr_field_type.');
  }

  /**
   * Get all available custom codes.
   *
   * @return string[]
   *   An array of custom codes as strings.
   */
  public static function getAvailableCustomCodes() {
    $custom_codes = [];
    $config_factory = \Drupal::configFactory();
    foreach ($config_factory->listAll('search_api_solr.solr_field_type.') as $field_type_name) {
      $config = $config_factory->get($field_type_name);
      if ($custom_code = $config->get('custom_code')) {
        $custom_codes[] = $custom_code;
      }
    }
    return array_unique($custom_codes);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeAsJson(bool $pretty = FALSE) {
    // Unfortunately the JSON encoded field type definition still uses the
    // element names "indexAnalyzer", "queryAnalyzer" and "multiTermAnalyzer"
    // which are deprecated in the XML format. Therefor we need to add some
    // conversion logic.
    $field_type = $this->fieldType;
    unset($field_type['analyzers']);

    foreach ($this->fieldType['analyzers'] as $analyzer) {
      $type = 'analyzer';
      if (!empty($analyzer['type'])) {
        if ('multiterm' === $analyzer['type']) {
          $type = 'multiTermAnalyzer';
        }
        else {
          $type = $analyzer['type'] . 'Analyzer';
        }
        unset($analyzer['type']);
      }
      $field_type[$type] = $analyzer;
    }

    /* @noinspection PhpComposerExtensionStubsInspection */
    return $pretty ?
      json_encode($field_type, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) :
      Json::encode($field_type);
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldTypeAsJson($field_type) {
    $field_type = $this->fieldType = Json::decode($field_type);

    // Unfortunately the JSON encoded field type definition still uses the
    // element names "indexAnalyzer", "queryAnalyzer" and "multiTermAnalyzer"
    // which are deprecated in the XML format. Therefore we need to add some
    // conversion logic.
    $analyzers = [
      'index' => 'indexAnalyzer',
      'query' => 'queryAnalyzer',
      'multiterm' => 'multiTermAnalyzer',
      'analyzer' => 'analyzer',
    ];
    foreach ($analyzers as $type => $analyzer) {
      if (!empty($field_type[$analyzer])) {
        unset($this->fieldType[$analyzer]);
        if ($type != $analyzer) {
          $field_type[$analyzer]['type'] = $type;
        }
        $this->fieldType['analyzers'][] = $field_type[$analyzer];
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSpellcheckFieldTypeAsJson(bool $pretty = FALSE) {
    if ($this->spellcheckFieldType) {
      /* @noinspection PhpComposerExtensionStubsInspection */
      return $pretty ?
        json_encode($this->spellcheckFieldType, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) :
        Json::encode($this->spellcheckFieldType);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setSpellcheckFieldTypeAsJson($spellcheck_field_type) {
    $this->spellcheckFieldType = Json::decode($spellcheck_field_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollatedFieldTypeAsJson(bool $pretty = FALSE) {
    if ($this->collatedFieldType) {
      /* @noinspection PhpComposerExtensionStubsInspection */
      return $pretty ?
        json_encode($this->collatedFieldType, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) :
        Json::encode($this->collatedFieldType);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setCollatedFieldTypeAsJson($collated_field_type) {
    $this->collatedFieldType = Json::decode($collated_field_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnstemmedFieldTypeAsJson(bool $pretty = FALSE) {
    if ($this->unstemmedFieldType) {
      /* @noinspection PhpComposerExtensionStubsInspection */
      return $pretty ?
        json_encode($this->unstemmedFieldType, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) :
        Json::encode($this->unstemmedFieldType);
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setUnstemmedFieldTypeAsJson($unstemmed_field_type) {
    $this->unstemmedFieldType = Json::decode($unstemmed_field_type);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAsXml(bool $add_comment = TRUE): string {
    return $this->getSubFieldTypeAsXml($this->fieldType);
  }

  /**
   * {@inheritdoc}
   */
  public function getSpellcheckFieldTypeAsXml($add_comment = TRUE) {
    return $this->spellcheckFieldType ?
      $this->getSubFieldTypeAsXml($this->spellcheckFieldType, ' spellcheck') : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getCollatedFieldTypeAsXml($add_comment = TRUE) {
    return $this->collatedFieldType ?
      $this->getSubFieldTypeAsXml($this->collatedFieldType, ' collated') : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getUnstemmedFieldTypeAsXml($add_comment = TRUE) {
    return $this->unstemmedFieldType ?
      $this->getSubFieldTypeAsXml($this->unstemmedFieldType, ' unstemmed') : '';
  }

  /**
   * Serializes a field type as XML fragment as required by Solr.
   *
   * @param array $field_type
   *   The filed type array.
   * @param string $additional_label
   *   An additioanl label to add to the XML fragment.
   * @param bool $add_comment
   *   Whether to add a comment or not. Default is to add a comment.
   *
   * @return string
   *   The XML fragment.
   */
  protected function getSubFieldTypeAsXml(array $field_type, string $additional_label = '', bool $add_comment = TRUE) {
    $formatted_xml_string = $this->buildXmlFromArray('fieldType', $field_type);

    $comment = '';
    if ($add_comment) {
      $comment = "<!--\n  " . $this->label() . $additional_label . "\n  " .
        $this->getMinimumSolrVersion() .
        "\n-->\n";
    }

    return $comment . $formatted_xml_string;
  }

  /**
   * {@inheritdoc}
   */
  public function getSolrConfigs() {
    return $this->solrConfigs;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicFields(?int $solr_major_version = NULL) {
    $dynamic_fields = [];

    $prefixes = $this->customCode ? [
      'tc' . $this->customCode,
      'toc' . $this->customCode,
      'tuc' . $this->customCode,
    ] : ['t', 'to', 'tu'];
    foreach ($prefixes as $prefix_without_cardinality) {
      foreach (['s', 'm'] as $cardinality) {
        $prefix = $prefix_without_cardinality . $cardinality;
        $name = $prefix . SolrBackendInterface::SEARCH_API_SOLR_LANGUAGE_SEPARATOR . $this->fieldTypeLanguageCode . '_';
        $dynamic_fields[] = $dynamic_field = [
          'name' => SearchApiSolrUtility::encodeSolrName($name) . '*',
          'type' => ((strpos($prefix, 'tu') === 0 && !empty($this->unstemmedFieldType)) ? $this->unstemmedFieldType['name'] : $this->fieldType['name']),
          'stored' => TRUE,
          'indexed' => TRUE,
          'multiValued' => ('m' === $cardinality),
          'termVectors' => TRUE,
          'omitNorms' => strpos($prefix, 'to') === 0,
        ];
        if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $this->fieldTypeLanguageCode) {
          // Add a language-unspecific default dynamic field as fallback for
          // languages we don't have a dedicated config for.
          $dynamic_field['name'] = SearchApiSolrUtility::encodeSolrName($prefix) . '_*';
          $dynamic_fields[] = $dynamic_field;
        }
      }
    }

    if ($spellcheck_field = $this->getSpellcheckField()) {
      // Spellcheck fields need to be dynamic to have a language fallback, for
      // example de-at => de.
      $dynamic_fields[] = $spellcheck_field;

      if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $this->fieldTypeLanguageCode) {
        // Add a language-unspecific default dynamic spellcheck field as
        // fallback for languages we don't have a dedicated config for.
        $spellcheck_field['name'] = 'spellcheck_*';
        $dynamic_fields[] = $spellcheck_field;
      }
    }

    if ($collated_field = $this->getCollatedField($solr_major_version)) {
      $dynamic_fields[] = $collated_field;

      if (LanguageInterface::LANGCODE_NOT_SPECIFIED === $this->fieldTypeLanguageCode) {
        // Add a language-unspecific default dynamic sort field as fallback for
        // languages we don't have a dedicated config for.
        $collated_field['name'] = 'sort_*';
        $dynamic_fields[] = $collated_field;
      }
    }

    return $dynamic_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getStaticFields() {
    return [];
  }

  /**
   * Returns the spellcheck field definition.
   *
   * @return array|null
   *   The array containing the spellcheck field definition or null if is
   *   not configured for this field type.
   */
  protected function getSpellcheckField() {
    $spellcheck_field = NULL;

    if ($this->spellcheckFieldType) {
      $spellcheck_field = [
        // Don't use the language separator here! This field name is used
        // without it in the solrconfig.xml. Due to the fact that we leverage a
        // dynamic field here to enable the language fallback we need to append
        // '*', but not '_*' because we'll never append a field name!
        'name' => 'spellcheck_' . $this->fieldTypeLanguageCode . '*',
        'type' => $this->spellcheckFieldType['name'],
        'stored' => TRUE,
        'indexed' => TRUE,
        'multiValued' => TRUE,
        'termVectors' => TRUE,
        'omitNorms' => TRUE,
      ];
    }

    return $spellcheck_field;
  }

  /**
   * Returns the collated field definition.
   *
   * @param int|null $solr_major_version
   *   Solr major version.
   *
   * @return array|null
   *   The array containing the collated field definition or null if is
   *   not configured for this field type.
   */
  protected function getCollatedField(?int $solr_major_version = NULL) {
    $collated_field = NULL;

    // Solr 3 and 4 need the sort field to be indexed and no docValues.
    if ($this->collatedFieldType) {
      $collated_field = [
        'name' => SearchApiSolrUtility::encodeSolrName('sort' . SolrBackendInterface::SEARCH_API_SOLR_LANGUAGE_SEPARATOR . $this->fieldTypeLanguageCode) . '_*',
        'type' => $this->collatedFieldType['name'],
        'stored' => FALSE,
        'indexed' => TRUE,
      ];

      if (version_compare($solr_major_version, '5', '>=')) {
        $collated_field['docValues'] = FALSE;
      }
    }

    return $collated_field;
  }

  /**
   * {@inheritdoc}
   */
  public function getCopyFields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeName() {
    return isset($this->fieldType['name']) ? $this->fieldType['name'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function requiresManagedSchema() {
    if (isset($this->fieldType['analyzers'])) {
      foreach ($this->fieldType['analyzers'] as $analyzer) {
        if (isset($analyzer['filters'])) {
          foreach ($analyzer['filters'] as $filter) {
            if (strpos($filter['class'], 'solr.Managed') === 0) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

}
