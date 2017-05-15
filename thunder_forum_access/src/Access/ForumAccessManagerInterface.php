<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides forum access manager interface.
 */
interface ForumAccessManagerInterface {

  /**
   * Return forum access record.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   *
   * @return \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface
   *   The forum access record.
   *
   * @throws \Exception
   */
  public function getForumAccessRecord(TermInterface $term);

  /**
   * Return list of forum members.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   *
   * @return \Drupal\user\UserInterface[]
   *   A list of members for the forum (or one of its parents by inheritance).
   */
  public function getForumMembers(TermInterface $term);

  /**
   * Return list of forum moderators.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   *
   * @return \Drupal\user\UserInterface[]
   *   A list of moderators for the forum (or one of its parents by
   *   inheritance).
   */
  public function getForumModerators(TermInterface $term);

  /**
   * Forum is protected from changes?
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   *
   * @return bool
   *   Whether the forum is locked / protected from changes  (or one of its
   *   parents by inheritance).
   */
  public function forumIsLocked(TermInterface $term);

  /**
   * Forum access is limited to associated members?
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   *
   * @return bool
   *   Whether the forum is private / its access is limited to associated
   *   members (or one of its parents by inheritance).
   */
  public function forumIsPrivate(TermInterface $term);

  /**
   * User is forum member?
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account object.
   *
   * @return bool
   *   Whether the given user is a member of the specified forum (or one of its
   *   parents by inheritance).
   */
  public function userIsForumMember(TermInterface $term, AccountInterface $account);

  /**
   * User is forum moderator?.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A forum term object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account object.
   *
   * @return bool
   *   Whether the given user is a moderator for the specified forum (or one of
   *   its parents by inheritance).
   */
  public function userIsForumModerator(TermInterface $term, AccountInterface $account);

}
