<?php

namespace Drupal\roebkes_misc\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RoebkesHeaderSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'roebkes_misc_header_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Use custom template file for theming.
    $form['#theme'] = 'roebkes_misc_header_search_form';

    // Add HTML5 novalidate attribute to form.
    $form['#attributes']['novalidate'] = 'novalidate';
    $form['#attributes']['autocomplete'] = 'off';

    // Set ID.
    $form['#id'] = 'roebkes-misc-header-search-form';

    // Attach library.
    $form['#attached']['library'][] = 'roebkes_misc/header_search_form';

    // Form fields:
    $form['query'] = array(
      '#type' => 'textfield',
      '#title' => 'Suchbegriff',
      '#title_display' => 'hidden',
      '#attributes' => [
        'placeholder' => 'Suchbegriff',
      ],
      '#weight' => 10,
    );

    // Taxonomy terms.

    $termNames = [];
    $termEntities = $this->loadTaxonomyTerms('search_categories');
    foreach ($termEntities as $termEntity) {
      $termNames[] = $termEntity->label();
    }
    $form['#search_categories'] = $termNames;

    $form['actions'] = array(
      '#type' => 'actions',
      '#weight' => 900,
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get query value.
    $query_value = Xss::filter(trim($form_state->getValue('query')));
    if (empty($query_value)) {
      return;
    }
    // Set query value as query parameter.
    $query_params = ['query' => $query_value];
    $url = "/search";

    // Redirect to search result page.
    $url = Url::fromUri("internal:$url", ['query' => $query_params]);
    $response = new RedirectResponse($url->toString());
    $response->send();
    exit();
  }

  /**
   * Load taxonomy terms by $vocabulary.
   *
   * @param string $vocabulary
   *
   * @return \Drupal\taxonomy\Entity\Term[]
   */
  private function loadTaxonomyTerms(string $vocabulary) {
    // Create an Entity Query for 'taxonomy_term'.
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vocabulary)
      ->sort('weight', 'ASC')
      ->range(0, 8)
      ->accessCheck(true);

    // Execute the query to get term IDs.
    $tids = $query->execute();

    // Load term entities by their IDs.
    $terms = Term::loadMultiple($tids);

    return $terms;
  }

}
