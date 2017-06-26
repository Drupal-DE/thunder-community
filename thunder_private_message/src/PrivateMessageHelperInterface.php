<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Session\AccountInterface;
use Drupal\message\MessageInterface;

/**
 * Provides private message helper interface.
 */
interface PrivateMessageHelperInterface {

  /**
   * Return message body.
   *
   * @param \Drupal\message\MessageInterface $message
   *   A message object.
   *
   * @return string|null
   *   The message body on success, otherwise NULL.
   */
  public function getMessageBody(MessageInterface $message);

  /**
   * Return message recipient.
   *
   * @param \Drupal\message\MessageInterface $message
   *   A message object.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The message recipient user on success, otherwise NULL.
   */
  public function getMessageRecipient(MessageInterface $message);

  /**
   * Return message subject.
   *
   * @param \Drupal\message\MessageInterface $message
   *   A message object.
   *
   * @return string|null
   *   The message subject on success, otherwise NULL.
   */
  public function getMessageSubject(MessageInterface $message);

  /**
   * Return number of unread messages for a user.
   *
   * This returns the number of messages matching the following criteria:
   *   - User is recipient of message.
   *   - User has not flagged message as deleted.
   *   - User has not read message yet.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $recipient
   *   The recipient user object (defaults to the current user).
   *
   * @return bool
   *   The number of unread messages.
   */
  public function getUnreadCount(AccountInterface $recipient = NULL);

  /**
   * Whether one user can write a private message to another user.
   *
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The recipient user object.
   * @param \Drupal\Core\Session\AccountInterface|null $sender
   *   The sender user object (defaults to the current user).
   *
   * @return bool
   *   Whether the sender may write a private message to the recipient.
   */
  public function userCanWriteMessageToOtherUser(AccountInterface $recipient, AccountInterface $sender = NULL);

  /**
   * Whether a user is allowed to bypass private message access checks.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object.
   *
   * @return bool
   *   Whether a user is allowed to bypass private message access checks.
   */
  public function userIsAllowedToBypassAccessChecks(AccountInterface $account);

  /**
   * Whether a user is the recipient of a private message.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   * @param \Drupal\message\MessageInterface $message
   *   A message object.
   *
   * @return bool
   *   Whether the user is recipient of the message.
   */
  public function userIsRecipient(AccountInterface $user, MessageInterface $message);

  /**
   * Whether a user is the sender of a private message.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   * @param \Drupal\message\MessageInterface $message
   *   A message object.
   *
   * @return bool
   *   Whether the user is sender of the message.
   */
  public function userIsSender(AccountInterface $user, MessageInterface $message);

}
