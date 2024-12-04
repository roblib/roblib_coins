<?php

declare(strict_types=1);

namespace Drupal\roblib_coins\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Configure Roblib COINS settings for this site.
 */
final class CoinSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CitationSelectSettingsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   *
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'roblib_coins_coin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['roblib_coins.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('roblib_coins.settings');
    $required_fields = [
      'Genre' => 'genre',
      'ISBN' => 'isbn',
      'Date Issued' => 'date_issued',
      'Authors' => 'authors',
      'DOI' => 'doi',
      'ISSN' => 'issn',
      'Page range' => 'page_range',
      'Publication title' => 'pub_title',
    ];
    $fields = $this->entityFieldManager->getFieldDefinitions('node', 'islandora_object');
    $entity_fields['none'] = '-none-';
    foreach ($fields as $field_name => $field_definition) {
      if (str_starts_with($field_name, 'field')) {
        $entity_fields[$field_name] = $field_definition->getLabel();
      }
    }
    $form['terms_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Coin param'),
        $this->t('Drupal Field'),
      ],
      '#attributes' => [
        'class' => ['terms-table'],
      ],
      '#prefix' => '<div id="terms-table-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['#attached']['library'][] = 'roblib_coins/terms_table';

    foreach ($required_fields as $display => $field_name) {
      $form['terms_table'][$field_name]['term_name'] = [
        '#plain_text' => $display,
      ];

      // Add a dropdown for each term.
      $form['terms_table'][$field_name]['dropdown'] = [
        '#type' => 'select',
        '#options' => $entity_fields,
        '#default_value' => $config->get('term_settings')[$field_name] ?? '',
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $term_settings = [];

    if (!empty($form_state->getValue('terms_table'))) {
      foreach ($form_state->getValue('terms_table') as $tid => $term_data) {
        $term_settings[$tid] = $term_data['dropdown'];
      }
    }
    $this->config('roblib_coins.settings')
      ->set('term_settings', $term_settings)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
