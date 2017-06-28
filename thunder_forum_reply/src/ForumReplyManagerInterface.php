<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

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
   * Returns number of new forum replies on a given forum entity for a user.
   *
   * Forum terms or forum nodes are allowed to get the count for.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The forum entity to get the new replies count for.
   * @param string $field_name
   *   (optional) The field_name to count forum replies for. Defaults to any
   *   field.
   * @param int $timestamp
   *   (optional) Time to count from. Defaults to time of last user access the
   *   entity.
   *
   * @return int|false
   *   The number of new forum replies. FALSE is returned if the user is not
   *   authenticated or the passed entity is no supported forum entity type.
   */
  public function getCountNewReplies(ContentEntityInterface $entity, $field_name = NULL, $timestamp = 0);

  /**
   * Returns TRUE if the given forum reply is not read by the given user yet.
   *
   * Unless the 'thunder_forum_reply_history' module is enabled, this method
   * returns always FALSE.
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
