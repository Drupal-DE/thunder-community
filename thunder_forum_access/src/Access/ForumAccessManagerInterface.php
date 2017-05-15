<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides forum access manager interface.
 */
interface ForumAccessManagerInterface {

  /**
   * Return forum access record.
   *
   * @param int $tid
   *   A forum taxonomy term ID.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface
   *   The forum access record on success.
   *
   * @throws \Exception
   */
  public function getForumAccessRecord($tid);

  /**
   * Return list of forum members.
   *
   * @param int $tid
   *   A forum taxonomy term ID.
   *
   * @return \Drupal\user\UserInterface[]
   *   A list of members for the forum (or one of its parents by inheritance).
   */
  public function getForumMembers($tid);

  /**
   * Return list of forum moderators.
   *
   * @param int $tid
   *   A forum taxonomy term ID.
   *
   * @return \Drupal\user\UserInterface[]
   *   A list of moderators for the forum (or one of its parents by
   *   inheritance).
   */
  public function getForumModerators($tid);

  /**
   * User is forum member?
   *
   * @param int $tid
   *   A forum taxonomy term ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account object.
   *
   * @return bool
   *   Whether the given user is a member of the specified forum (or one of its
   *   parents by inheritance).
   */
  public function userIsForumMember($tid, AccountInterface $account);

  /**
   * User is forum moderator?.
   *
   * @param int $tid
   *   A forum taxonomy term ID.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account object.
   *
   * @return bool
   *   Whether the given user is a moderator for the specified forum (or one of
   *   its parents by inheritance).
   */
  public function userIsForumModerator($tid, AccountInterface $account);

}
