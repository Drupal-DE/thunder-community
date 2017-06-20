<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Session\AccountInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager interface.
 */
interface ThunderForumManagerInterface extends ForumManagerInterface {

  /**
   * Return term IDs of all forum term children.
   *
   * @param $tid
   *   A forum term ID.
   * @return array
   *   An array of forum term IDs.
   */
  public function getChildTermIds($tid);

  /**
   * Provides statistics for a forum.
   *
   * @param int $tid
   *   The forum tid.
   *
   * @return \stdClass|null
   *   Statistics for the given forum if statistics exist, else NULL.
   */
  public function getForumStatistics($tid);

  /**
   * Return forum taxonomy term for forum node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A forum node.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The forum taxonomy term on success, otherwise NULL.
   */
  public function getForumTermByNode(NodeInterface $node);

  /**
   * Provides the last post information for the given forum tid.
   *
   * @param int $tid
   *   The forum tid.
   *
   * @return \stdClass
   *   The last post for the given forum with the following properties:
   *     - created: The last post's created timestamp.
   *     - name: The name of the last post's author.
   *     - uid: The user ID of the last post's author.
   *     - entity_id: The entity ID of the last post.
   *     - entity_type_id: The entity type of the last post (either 'node' or
   *       'thunder_forum_reply').
   */
  public function getLastPost($tid);

  /**
   * Utility method to fetch the direct ancestor forum for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent for.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The parent forum taxonomy term on success, otherwise NULL.
   */
  public function getParent($tid);

  /**
   * Utility method to fetch the direct ancestor forum ID for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent ID for.
   *
   * @return int
   *   The parent forum taxonomy term ID on success, otherwise '0'.
   */
  public function getParentId($tid);

  /**
   * Returns TRUE if the given taxonomy term is a forum container.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum container.
   */
  public function isForumContainer(TermInterface $term);

  /**
   * Returns TRUE if the given taxonomy term is a forum term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum term.
   */
  public function isForumTerm(TermInterface $term);

  /**
   * Returns TRUE if the given form ID is for a forum taxonomy term form.
   *
   * @param string $form_id
   *   A form ID.
   *
   * @return bool
   *   Boolean indicating whether the given form ID is for a forum taxonomy term
   *   form.
   */
  public function isForumTermForm($form_id);

  /**
   * Returns TRUE if any node below the given forum term has new/unread replies.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object.
   *
   * @return bool
   *   Boolean indicating whether any node below the given forum term has
   *   new/unread replies.
   */
  public function isForumWithNewReplies(TermInterface $term, AccountInterface $account);

  /**
   * Returns TRUE if the given forum node is hot.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A forum node.
   *
   * @return bool
   *   Boolean indicating whether the given forum node is hot (has more replies
   *   than the threshold to be considered "hot".
   */
  public function isHotTopic(NodeInterface $node);

  /**
   * Returns TRUE if the given forum node has new/unread replies.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A forum node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object.
   *
   * @return bool
   *   Boolean indicating whether the given forum node has new/unread replies.
   */
  public function isTopicWithNewReplies(NodeInterface $node, AccountInterface $account);

  /**
   * Returns TRUE if the given forum node was not read by the given user yet.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A forum node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object.
   *
   * @return bool
   *   Boolean indicating whether the given forum node was not read by the given
   *   user yet.
   */
  public function isUnreadTopic(NodeInterface $node, AccountInterface $account);

}
