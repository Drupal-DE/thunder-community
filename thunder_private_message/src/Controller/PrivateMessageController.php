<?php

namespace Drupal\thunder_private_message\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\message\MessageInterface;
use Drupal\message\MessageTemplateInterface;
use Drupal\message_ui\Controller\MessageController;

/**
 * Controller for adding private messages.
 */
class PrivateMessageController extends MessageController {

  /**
   * Generates form output for adding a new private message.
   *
   * @param \Drupal\message\MessageTemplateInterface $message_template
   *   The message template object.
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   (optional) The message recipient.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function addMessage(MessageTemplateInterface $message_template, AccountInterface $recipient = NULL) {
    $form = parent::add($message_template);

    if (empty($recipient)) {
      // No need to do any alterations.
      return $form;
    }

    if (isset($form['tpm_recipient']['widget'][0]) && empty($form['tpm_recipient']['widget'][0]['target_id']['#default_value'])) {
      $form['tpm_recipient']['widget'][0]['target_id']['#default_value'] = $recipient;
    }
    return $form;
  }

  /**
   * Generates form output for replying to a private message.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function reply(MessageInterface $message) {
    return $this->entityFormBuilder()->getForm($message, 'pm-reply');
  }

  /**
   * Checks access for reply links.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function replyAccess(MessageInterface $message, AccountInterface $account) {
    // Only message recipients are allowed to reply to a private message.
    return AccessResult::allowedIf($message->get('tpm_recipient')->first()->entity->id() === $account->id());
  }

}
