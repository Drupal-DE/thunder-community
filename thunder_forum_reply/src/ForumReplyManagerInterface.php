<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Forum reply manager contains common functions to manage forum reply fields.
 */
interface ForumReplyManagerInterface {

  /**
   * Utility function to return an array of forum reply fields.
   *
   * @return array
   *   An array of forum reply field map definitions, keyed by field name. Each
   *   value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears, as an array with
   *     entity types as keys and the array of bundle names as values.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldMap()
   */
  public function getFields();

  /**
   * Returns number of new forum replies on a given forum node for a user.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The forum node to which the forum replies are attached to.
   * @param string $field_name
   *   (optional) The field_name to count forum replies for. Defaults to any
   *   field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to time of last user access the
   *   entity.
   *
   * @return int|false
   *   The number of new forum replies or FALSE if the user is not
   *   authenticated.
   */
  public function getCountNewReplies(NodeInterface $node, $field_name = NULL, $timestamp = 0);

  /**
   * Returns TRUE if the given forum node reply not read by the given user yet.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   A forum reply.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object.
   *
   * @return bool
   *   Boolean indicating whether the given forum reply was not read by the
   *   given user yet.
   */
  public function isUnreadReply(ForumReplyInterface $reply, AccountInterface $account);

}
