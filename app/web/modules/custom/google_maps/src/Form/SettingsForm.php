<?php

namespace Drupal\google_maps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google Maps settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_maps_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_maps.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Gmap settings.
    $form['gmap'] = array(
      '#type' => 'fieldset',
      '#title' => t('Google Maps settings'),
      '#tree' => TRUE,
    );

    $form['gmap']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Google API Key'),
      '#description' => 'API key must provide the following services: Maps JS & Geocoding API',
      '#default_value' => $this->config('google_maps.settings')->get('gmap.api_key'),
      '#required' => TRUE,
    );

    $form['gmap']['type'] = array(
      '#type' => 'select',
      '#title' => t('Map Type'),
      '#default_value' => $this->config('google_maps.settings')->get('gmap.type'),
      '#options' => array(
        'roadmap' => 'roadmap',
        'satellite' => 'satellite',
        'hybrid' => 'hybrid',
        'terrain' => 'terrain',
      ),
      '#required' => TRUE,
    );

    $zoom_options = array();
    for ($i = 0; $i <= 18; $i++) {
      $zoom_options[$i] = $i;
    }
    $form['gmap']['zoom'] = array(
      '#type' => 'select',
      '#title' => t('Default zoom'),
      '#options' => $zoom_options,
      '#default_value' => $this->config('google_maps.settings')->get('gmap.zoom'),
      '#required' => TRUE,
    );

    $form['gmap']['controls'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Map Controls'),
      '#default_value' => $this->config('google_maps.settings')->get('gmap.controls') ? $this->config('google_maps.settings')->get('gmap.controls') : [],
      '#options' => array(
        'zoom' => t('Display %control control', array('%control' => 'zoom')),
        'maptype' => t('Display %control control', array('%control' => 'map type')),
        'scale' => t('Display %control control', array('%control' => 'scale')),
        'streetview' => t('Display %control control', array('%control' => 'streetview')),
        'rotate' => t('Display %control control', array('%control' => 'rotate')),
        'fullscreen' => t('Display %control control', array('%control' => 'fullscreen')),
      ),
    );

    $form['gmap']['map_style'] = array(
      '#title' => t('Map Style, e.g. from snazzymaps.com'),
      '#type' => 'textarea',
      '#description' => t('Paste your JSON Style-Export from <a href="http://snazzymaps.com" target="_blank">http://snazzymaps.com</a> or leave empty for default gmap style.'),
      '#default_value' => $this->config('google_maps.settings')->get('gmap.map_style'),
      '#cols' => 10,
      '#rows' => 10,
    );

    $form['gmap']['map_style_static'] = array(
      '#title' => t('Map Style for use in static maps'),
      '#type' => 'textarea',
      '#description' => t('Paste your JSON Style-Export from <a href="https://mapstyle.withgoogle.com/" target="_blank">https://mapstyle.withgoogle.com/</a> or leave empty for default gmap style.'),
      '#default_value' => $this->config('google_maps.settings')->get('gmap.map_style_static'),
      '#cols' => 10,
      '#rows' => 10,
    );

    $form['gmap']['custom_marker'] = array(
      '#type' => 'textfield',
      '#title' => t('Primary Custom Marker Image'),
      '#description' => 'Primary custom marker, insert file-path, eg. /sites/default/files/gmap_marker/marker.png',
      '#default_value' => $this->config('google_maps.settings')->get('gmap.custom_marker'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => FALSE,
    );

    $form['gmap']['custom_marker_secondary'] = array(
      '#type' => 'textfield',
      '#title' => t('Secondary Custom Marker Image'),
      '#description' => 'Secondary custom marker, insert file-path, eg. /sites/default/files/gmap_marker/marker-secondary.png',
      '#default_value' => $this->config('google_maps.settings')->get('gmap.custom_marker_secondary'),
      '#size' => 60,
      '#maxlength' => 255,
      '#required' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('google_maps.settings')
      ->set('gmap', $form_state->getValue('gmap'))
      ->save();
    parent::submitForm($form, $form_state);

    // Clear all caches.
    drupal_flush_all_caches();
  }
}
