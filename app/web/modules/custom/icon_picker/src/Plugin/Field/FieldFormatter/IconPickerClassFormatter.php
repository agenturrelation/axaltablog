<?php

namespace Drupal\icon_picker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Custom plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "icon_picker_class",
 *   label = @Translation("CSS Class"),
 *   field_types = {
 *     "icon_picker"
 *   }
 * )
 */
class IconPickerClassFormatter extends StringFormatter {
  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    return [
      '#markup' => '<span class="material-symbols-outlined">' . $item->value . '</i>',
    ];
  }
}
