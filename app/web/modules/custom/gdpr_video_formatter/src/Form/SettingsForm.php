<?php

namespace Drupal\gdpr_video_formatter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GDPR Video Formatter settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_video_formatter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdpr_video_formatter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('gdpr_video_formatter.settings');

    // YouTube API:
    $form['youtube_api'] = [
      '#type' => 'fieldset',
      '#title' => 'YouTube API',
      '#tree' => TRUE,
    ];

    $form['youtube_api']['fetch_metadata'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enable fetching metadata (description, upload date) of uploaded YouTube videos'),
      '#default_value' => $config->get('youtube_api.fetch_metadata'),
    );

    $form['youtube_api']['api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('YouTube API Key'),
      '#default_value' =>  $config->get('youtube_api.api_key'),
    );

    // Texts:
    $form['texts'] = [
      '#type' => 'details',
      '#title' => $this->t('GDPR consent texts and confirmation button label'),
      '#open' => TRUE,
    ];

    $form['texts']['consent_text_youtube'] = [
      '#type' => 'textarea',
      '#title' => $this->t('GDPR consent text for YouTube, if used'),
      '#default_value' => $config->get('consent_text_youtube'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('gdpr_video_formatter.settings');
    $keys = [
      'youtube_api',
      'consent_text_youtube',
    ];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
