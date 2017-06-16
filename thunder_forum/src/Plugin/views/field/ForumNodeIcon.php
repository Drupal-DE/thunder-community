<?php

namespace Drupal\thunder_forum\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display a forum node's icon.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_node_icon")
 */
class ForumNodeIcon extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $nid = $this->getValue($values);

    return [
      '#lazy_builder' => [
        'thunder_forum.lazy_builder:renderIcon',
        [
          'node',
          $nid,
        ],
      ],
    ];
  }

}
