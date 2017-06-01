<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Defines an interface for forum reply entity storage classes.
 */
interface ForumReplyStorageInterface extends ContentEntityStorageInterface {

  /**
   * Unsets the language for all forum replies with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   The forum reply entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ForumReplyInterface $reply);

  /**
   * Gets the forum reply IDs of the passed forum reply entities' children.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface[] $replies
   *   An array of forum reply entities keyed by their IDs.
   *
   * @return array
   *   The entity IDs of the passed forum reply entities' children as an array.
   */
  public function getChildReplyIds(array $replies);

  /**
   * Gets the display ordinal or page number for a forum reply.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   The forum reply to use as a reference point.
   * @param int $divisor
   *   Defaults to 1, which returns the display ordinal for a forum reply. If
   *   the number of forum replies per page is provided, the returned value will
   *   be the page number (the return value will be divided by $divisor).
   *
   * @return int
   *   The display ordinal or page number for the forum reply. It is 0-based, so
   *   will represent the number of items before the given forum reply/page.
   */
  public function getDisplayOrdinal(ForumReplyInterface $reply, $divisor = 1);

  /**
   * Calculates the page number for the first new forum reply.
   *
   * @param int $total_replies
   *   The total number of forum replies that the entity has.
   * @param int $new_replies
   *   The number of new forum replies that the entity has.
   * @param \Drupal\node\NodeInterface $node
   *   The node entity to which the forum reply belongs.
   * @param string $field_name
   *   The field name on the entity to which forum replies are attached.
   *
   * @return array|null
   *   The page number where first new forum reply appears (First page returns
   *   0).
   */
  public function getNewReplyPageNumber($total_replies, $new_replies, NodeInterface $node, $field_name);

  /**
   * Retrieves forum replies, sorted in an order suitable for display.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The entity whose forum reply(s) needs rendering.
   * @param string $field_name
   *   The field_name whose forum reply(s) needs rendering.
   * @param int $replies_per_page
   *   (optional) The amount of forum replies to display per page.
   *   Defaults to 0, which means show all forum replies.
   * @param int $pager_id
   *   (optional) Pager ID to use in case of multiple pagers on the one page.
   *   Defaults to 0; is only used when $replies_per_page is greater than zero.
   *
   * @return array
   *   Ordered array of forum reply objects, keyed by forum reply ID.
   */
  public function loadThread(NodeInterface $node, $field_name, $replies_per_page = 0, $pager_id = 0);

  /**
   * Gets a list of forum reply revision IDs for a specific forum reply.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   The forum reply entity.
   *
   * @return int[]
   *   Forum reply revision IDs (in ascending order).
   */
  public function revisionIds(ForumReplyInterface $reply);

  /**
   * Gets a list of revision IDs having a given user as forum reply author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Forum reply revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}
