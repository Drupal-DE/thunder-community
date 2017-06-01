<?php

namespace Drupal\thunder_forum_reply\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * Filter handler for the newer of last forum reply / node updated.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("thunder_forum_reply_statistics_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $this->node_table = $this->query->ensureTable('node', $this->relationship);
    $field = "GREATEST(" . $this->node_table . ".changed, " . $this->tableAlias . ".last_reply_timestamp)";

    $info = $this->operators();

    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
