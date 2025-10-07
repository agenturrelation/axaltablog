<?php

namespace Drupal\anchor_toc\Plugin\Field\FieldWidget;

use Drupal\anchor_toc\Plugin\AnchorTocDefaultItemsInterface;
use Drupal\anchor_toc\Plugin\Field\FieldType\AnchorTocItem;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'anchor_toc' field widget.
 *
 * @FieldWidget(
 *   id = "anchor_toc",
 *   label = @Translation("Anchor TOC"),
 *   field_types = {"anchor_toc"},
 * )
 */
class AnchorTocWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var  AnchorTocItem $item */
    $item = $items[$delta];

    // Get UUID service.
    $uuid_service = \Drupal::service('uuid');

    // dsm($item);

    // Set up the form element for this widget.
    $element += [
      '#type' => 'details',
      '#open' => TRUE,
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    // Build TOC options.
    $tocOptions =  [
      'none' => $this->t('None'),
      'custom' => $this->t('Custom'),
    ];

    // Get default items from Plugins.
    $plugin_manager = \Drupal::service('plugin.manager.anchor_toc_default_items');
    $plugin_definitions = $plugin_manager->getDefinitions();
    // dsm($plugin_definitions);
    foreach ($plugin_definitions as $plugin_definition) {
      /** @var AnchorTocDefaultItemsInterface $plugin_instance */
      $plugin_instance = $plugin_manager->createInstance($plugin_definition['id']);
      $defaultItems = $plugin_instance->getDefaultItems();
      if (count($defaultItems) > 0) {
        // Add default items to TOC options, keyed by plugin label.
        foreach ($defaultItems as $defaultItem) {
          /** @var TranslatableMarkup $plugin_label */
          $plugin_label = $plugin_definition['label'];
          $tocOptions[$plugin_label->render()][$plugin_definition['id'] . '__' . $defaultItem['ident']] = $defaultItem['label'];
        }
      }
    }

    // TOC.
    $toc_states_selector = "toc-" . $uuid_service->generate();
    $element['toc']  = [
      '#type' => 'select',
      '#title' => $this->t('Table of Contents Item'),
      // '#title_display' => 'hidden',
      '#description' => $this->t('Please select an item or choose "custom" to create a custom TOC item'),
      '#default_value' => $item->__get('toc'),
      '#attributes' => [
        'data-toc-states-selector' => $toc_states_selector,
      ],
      '#options' => $tocOptions,
      '#required' => $element['#required'],
    ];

    // Label.
    $element['label']  = [
      '#type' => 'textfield',
      '#title' => $this->t('TOC: Label'),
      '#description' => $this->t('The label is shown as link'),
      '#default_value' => $item->__get('label'),
      '#maxlength' => 128,
      '#states' => [
        'visible' => [
          'select[data-toc-states-selector="' . $toc_states_selector . '"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $element['custom_anchor']  = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check to provide a custom anchor'),
      '#default_value' => !empty($item->__get('anchor_id')),
      '#attributes' => [
        'data-toc-states-selector' => $toc_states_selector . '-custom-anchor',
      ],
      '#states' => [
        'visible' => [
          'select[data-toc-states-selector="' . $toc_states_selector . '"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $element['anchor_id']  = [
      '#type' => 'textfield',
      '#title' => $this->t('TOC: Anchor ID'),
      '#default_value' => $item->__get('anchor_id'),
      '#maxlength' => 128,
      '#states' => [
        'visible' => [
          'input[data-toc-states-selector="' . $toc_states_selector . '-custom-anchor"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Validate the fields and convert them into a single value as text.
   */
  public function validate($element, FormStateInterface $form_state) {
    // dsm($element);
    // Check label / anchor ID.
    if ($element['toc']['#value'] == 'custom') {
      if (empty($element['label']['#value'])) {
        $form_state->setError($element['label'], $this->t('Please provide a valid label.'));
      }
      if (!empty($element['custom_anchor']['#value']) && empty($element['anchor_id']['#value'])) {
        $form_state->setError($element['anchor_id'], $this->t('Please provide a valid anchor.'));
      } else if (empty($element['custom_anchor']['#value'])) {
        // Update value with empty anchor ID.
        $form_state->setValueForElement($element, [
          'toc' => $element['toc']['#value'],
          'label' => $element['label']['#value'],
          'anchor_id' => '',
        ]);
      }
    }
  }
}
