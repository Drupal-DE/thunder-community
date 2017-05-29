<?php

namespace Drupal\thunder_forum_reply;

use Drupal\node\NodeInterface;

/**
 * Provides an interface for storing and retrieving forum reply statistics.
 */
interface ForumReplyStatisticsInterface {

  /**
   * Read forum reply statistics records for an array of forum nodes.
   *
   * @param \Drupal\node\NodeInterface[] $nodes
   *   Array of forum nodes on which forum replies are enabled, keyed by ID.
   * @param bool $accurate
   *   (optional) Indicates if results must be completely up to date. If set to
   *   FALSE, a replica database will be used if available. Defaults to TRUE.
   *
   * @return object[]
   *   Array of statistics records.
   */
  public function read(array $nodes, $accurate = TRUE);

  /**
   * Delete forum reply statistics records for a forum node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The forum node for which forum reply statistics should be deleted.
   */
  public function delete(NodeInterface $node);

  /**
   * Update or insert forum reply statistics records after forum reply is added.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   The forum reply added or updated.
   */
  public function update(ForumReplyInterface $reply);

  /**
   * Find the maximum number of forum replies.
   *
   * Used to influence search rankings.
   *
   * @return int
   *   The maximum number of forum replies.
   *
   * @see thunder_forum_reply_update_index()
   */
  public function getMaximumCount();

  /**
   * Insert an empty record for the given forum node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The created forum node for which a statistics record is to be
   *   initialized.
   * @param array $fields
   *   Array of forum reply field definitions for the given forum node.
   */
  public function create(NodeInterface $node, array $fields);

}
