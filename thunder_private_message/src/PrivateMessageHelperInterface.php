<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides private message helper interface.
 */
interface PrivateMessageHelperInterface {

  /**
   * Whether one user can write a private message to another user.
   *
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The recipient user object.
   * @param \Drupal\Core\Session\AccountInterface|null $sender
   *   The sender user object.
   *
   * @return bool
   *   Whether the sender may write a private message to the recipient.
   */
  public function userCanWriteMessageToOtherUser(AccountInterface $recipient, AccountInterface $sender = NULL);

}
