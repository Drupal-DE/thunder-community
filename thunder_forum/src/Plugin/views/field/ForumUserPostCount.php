<?php

namespace Drupal\thunder_forum\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to display a forum user's post count.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_user_post_count")
 */
class ForumUserPostCount extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $uid = $this->getValue($values);

    return [
      '#lazy_builder' => [
        'thunder_forum.lazy_builder:renderUserPostCount',
        [
          $uid,
          // Provide theme suggestions based on view/display/field.
          'views_view_field__' . $this->view->id() . '__' . $this->view->current_display . '__' . $this->getPluginId(),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
  }

}
