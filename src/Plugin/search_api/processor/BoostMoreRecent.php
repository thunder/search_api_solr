<?php

namespace Drupal\search_api_solr\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;

/**
 * Adds a boost for more recent dates.
 *
 * @SearchApiProcessor(
 *   id = "solr_boost_more_recent",
 *   label = @Translation("Boost more recent dates"),
 *   description = @Translation("Boost more recent documents and penalize older documents."),
 *   stages = {
 *     "preprocess_query" = 0,
 *   }
 * )
 */
class BoostMoreRecent extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  public const FIELD_PLACEHOLDER = 'FIELD_PLACEHOLDER';

  /**
   * The available boost factors.
   *
   * @var string[]
   */
  protected static $boost_factors = [
    '0.0' => '0.0',
    '0.1' => '0.1',
    '0.2' => '0.2',
    '0.3' => '0.3',
    '0.5' => '0.5',
    '0.8' => '0.8',
    '1.0' => '1.0',
    '2.0' => '2.0',
    '3.0' => '3.0',
    '5.0' => '5.0',
    '8.0' => '8.0',
    '13.0' => '13.0',
    '21.0' => '21.0',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boosts' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    foreach ($this->index->getFields(TRUE) as $field_id => $field) {
      if ('date' === $field->getType()) {
        $form['boosts'][$field_id] = [
          '#type' => 'details',
          '#title' => $field->getLabel(),
        ];

        $form['boosts'][$field_id]['boost'] = [
          '#type' => 'select',
          '#title' => $this->t('Boost'),
          '#options' => static::$boost_factors,
          '#description' => $this->t('To boost more recent dates, Solr performs a reciprocal function with recip(x,m,a,b) implementing a/(m*x+b) where m,a,b are constants, and x is a date converted into the difference between NOW and its timestamp using a configurable resolution. When a and b are equal, and x>=0, this function has a maximum value of 1 that drops as x increases. Increasing the value of a and b together results in a movement of the entire function to a flatter part of the curve. These properties make this an ideal function for boosting more recent documents. Therefore its result is multiplied with a configurable boost factor. Setting it to 0.0 disables the boost by recent date for this field.'),
          '#default_value' => sprintf('%.1f', $this->configuration['boosts'][$field_id]['boost'] ?? 0.0),
        ];

        $form['boosts'][$field_id]['resolution'] = [
          '#type' => 'select',
          '#title' => $this->t('Resolution'),
          '#options' => [
            'NOW' => $this->t('milliseconds'),
            'NOW/SECOND' => $this->t('seconds'),
            'NOW/MINUTE' => $this->t('minutes'),
            'NOW/HOUR' => $this->t('hours'),
            'NOW/DAY' => $this->t('days'),
            'NOW/WEEK' => $this->t('weeks'),
            'NOW/MONTH' => $this->t('months'),
            'NOW/YEAR' => $this->t('years'),
          ],
          '#default_value' => $this->configuration['boosts'][$field_id]['resolution'] ?? 'NOW',
        ];

        $form['boosts'][$field_id]['m'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Constant m'),
          '#default_value' => $this->configuration['boosts'][$field_id]['m'] ?? '3.16e-11',
        ];

        $form['boosts'][$field_id]['a'] = [
          '#type' => 'number',
          '#step' => 0.01,
          '#title' => $this->t('Constant a'),
          '#default_value' => $this->configuration['boosts'][$field_id]['a'] ?? 0.1,
        ];

        $form['boosts'][$field_id]['b'] = [
          '#type' => 'number',
          '#step' => 0.01,
          '#title' => $this->t('Constant b'),
          '#default_value' => $this->configuration['boosts'][$field_id]['b'] ?? 0.05,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['boosts'] as $field_id => $boost) {
      if (0 == $boost['boost']) {
        unset($values['boosts'][$field_id]);
      }
    }
    $form_state->setValues($values);
    $this->setConfiguration($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    parent::preprocessSearchQuery($query);

    $boosts = [];
    foreach ($this->configuration['boosts'] as $field_id => $boost) {
      $boosts[$field_id] = sprintf('product(%.1f,recip(ms(%s,%s),%s,%.3f,%3f))', $boost['boost'], $boost['resolution'], self::FIELD_PLACEHOLDER, $boost['m'], $boost['a'], $boost['b']);
    }
    if ($boosts) {
      $query->setOption('solr_boost_more_recent', $boosts);
    }
  }

}
