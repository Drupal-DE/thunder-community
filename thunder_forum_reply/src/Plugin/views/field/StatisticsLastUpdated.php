<?php

namespace Drupal\thunder_forum_reply\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;

/**
 * Field handler to display the newer of last forum reply / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_reply_statistics_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $this->node_table = $this->query->ensureTable('node_field_data', $this->relationship);
    $this->field_alias = $this->query->addField(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_reply_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
