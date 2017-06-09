<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides private message helper service.
 */
class PrivateMessageHelper implements PrivateMessageHelperInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PrivateMessageHelper.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanWriteMessageToOtherUser(AccountInterface $recipient, AccountInterface $sender = NULL) {
    $sender = isset($sender) ? $sender : $this->currentUser;

    // Is administrator?
    if ($sender->hasPermission('bypass thunder_private_message access') || $sender->hasPermission('administer thunder_private_message')) {
      return TRUE;
    }

    // User is allowed to write private messages?
    elseif ($sender->hasPermission('create thunder_private_message message')) {
      // Recipient allows private messages?
      return !$recipient->hasField('tpm_allow_messages') || !$recipient->tpm_allow_messages->isEmpty() || $recipient->tpm_allow_messages->value;
    }

    return FALSE;
  }

}
