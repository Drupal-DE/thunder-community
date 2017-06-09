<?php

namespace Drupal\thunder_friends;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for friendship entities.
 */
interface FriendshipInterface {

  /**
   * Get the ID of the current user for baseFieldDefinitions.
   *
   * @return array
   *   Array with the current user ID.
   */
  public static function getCurrentUserId();

  /**
   * Get user object of friendship initiator.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The friendship initiator.
   */
  public function getInitiator();

  /**
   * Get the user ID of the friendship initiator.
   *
   * @return int
   *   The user ID.
   */
  public function getInitiatorId();

  /**
   * Set friendship initiator.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object.
   */
  public function setInitiator(AccountInterface $account);

  /**
   * Set friendship initiator ID.
   *
   * @param int $uid
   *   ID of user object.
   */
  public function setInitiatorId($uid);

  /**
   * Get user object of friendship friend.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The friend.
   */
  public function getFriend();

  /**
   * Get the user ID of the friendship friend.
   *
   * @return int
   *   The user ID.
   */
  public function getFriendId();

  /**
   * Set friendship friend.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object.
   */
  public function setFriend(AccountInterface $account);

  /**
   * Set friendship friend ID.
   *
   * @param int $uid
   *   ID of user object.
   */
  public function setFriendId($uid);

  /**
   * Get the current friendship status.
   *
   * @return int
   *   One of Friendship::REQUESTED, Friendship::APPROVED, Friendship::DECLINED.
   */
  public function getStatus();

  /**
   * Set the friendship status.
   *
   * @param int $status
   *   One of Friendship::REQUESTED, Friendship::APPROVED, Friendship::DECLINED.
   */
  public function setStatus($status);

}
