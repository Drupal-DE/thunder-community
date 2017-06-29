<?php

namespace Drupal\thunder_private_message\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display a private message's icon.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_private_message_icon")
 */
class PrivateMessageIcon extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $mid = $this->getValue($values);

    return [
      '#lazy_builder' => [
        'thunder_private_message.lazy_builder:renderIcon',
        [
          $mid,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

}
