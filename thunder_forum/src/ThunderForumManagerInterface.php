<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Session\AccountInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager interface.
 */
interface ThunderForumManagerInterface extends ForumManagerInterface {

  /**
   * Utility method to fetch the direct ancestor forum for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent for.
   *
   * @return \Drupal\taxonomy\TermInterface[]|null
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
   * Returns TRUE if the forum is private (and thus only accessible by members).
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   *
   * @return bool
   *   TRUE if the forum is private.
   */
  public function isPrivate(TermInterface $term);

  /**
   * Returns TRUE if the forum is locked.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   *
   * @return bool
   *   TRUE if the forum is locked.
   */
  public function isLocked(TermInterface $term);

  /**
   * Get list of moderators of the forum.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   *
   * @return \Drupal\user\UserInterface[]
   *   List of forum moderators.
   */
  public function getModerators(TermInterface $term);

  /**
   * Get list of members associated to the forum.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   *
   * @return \Drupal\user\UserInterface[]
   *   List of forum members.
   */
  public function getMembers(TermInterface $term);

  /**
   * Returns TRUE if the given account is a moderator of the forum.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check.
   *
   * @return bool
   *   TRUE if the given account is a moderator of the forum.
   */
  public function isModerator(TermInterface $term, AccountInterface $account);

  /**
   * Returns TRUE if the given account is a member of the forum.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   Forum term.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account to check.
   *
   * @return bool
   *   TRUE if the given account is a member of the forum.
   */
  public function isMember(TermInterface $term, AccountInterface $account);

}
