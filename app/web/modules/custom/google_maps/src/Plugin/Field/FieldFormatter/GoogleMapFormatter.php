<?php

namespace Drupal\google_maps\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_maps\GoogleMapsTheming;

/**
 * Plugin implementation of the 'Google Map' formatter.
 *
 * @FieldFormatter(
 *   id = "google_map",
 *   label = @Translation("Google Map"),
 *   field_types = {"google_map_marker"},
 * )
 */
final class GoogleMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $setting = [
      'routes' => FALSE,
      'legend' => TRUE,
    ];
    return $setting + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements['routes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show routes between markers'),
      '#default_value' => $this->getSetting('routes'),
    ];
    $elements['legend'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show legend'),
      '#default_value' => $this->getSetting('legend'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [
      $this->t('Routes between markers: @val', ['@val' => !empty($this->getSetting('routes')) ? t('Yes') : t('No')]),
      $this->t('Legend: @val', ['@val' => !empty($this->getSetting('legend')) ? t('Yes') : t('No')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {

    // Build markers.
    $markers = [];
    foreach ($items as $item) {
      $values = $item->getValue();
      unset($values['_attributes']);
      $markers[] = $values;
    }

    // Return themed map.
    $theming = new GoogleMapsTheming();
    return $theming->themeGoogleMap($markers, !empty($this->getSetting('routes')), !empty($this->getSetting('legend')));
  }

}
