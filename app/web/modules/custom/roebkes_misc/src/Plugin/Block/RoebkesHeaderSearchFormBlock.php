<?php

namespace Drupal\roebkes_misc\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block.
 *
 * @Block(
 *   id = "roebkes_header_search_form_block",
 *   admin_label = @Translation("roebkes: Header Search Form Block"),
 *   category = @Translation("Roebkes")
 * )
 */
class RoebkesHeaderSearchFormBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\roebkes_misc\Form\RoebkesHeaderSearchForm');
  }
}
