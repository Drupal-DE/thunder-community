<?php

namespace Drupal\thunder_forum_reply\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to display timestamp of forum reply with count of forum replies.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_reply_last_timestamp")
 */
class LastTimestamp extends Date {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['reply_count'] = 'reply_count';
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $reply_count = $this->getValue($values, 'reply_count');

    if (empty($this->options['empty_zero']) || $reply_count) {
      return parent::render($values);
    }

    return NULL;
  }

}
