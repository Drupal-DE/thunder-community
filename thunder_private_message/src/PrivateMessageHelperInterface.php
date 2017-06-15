<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides private message helper interface.
 */
interface PrivateMessageHelperInterface {

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

}
