<?php

namespace Drupal\thunder_forum\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display a forum taxonomy term's icon.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_term_icon")
 */
class ForumTermIcon extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $tid = $this->getValue($values);

    return [
      '#lazy_builder' => [
        'thunder_forum.lazy_builder:renderIcon',
        [
          'taxonomy_term',
          $tid,
        ],
      ],
    ];
  }

}
