<?php

namespace Drupal\thunder_private_message\Plugin\NotificationSource;

use Drupal\thunder_notify\NotificationSourceBase;

/**
 * Notification source for private messages.
 *
 * @NotificationSource(
 *   id = "private_message",
 *   label = @Translation("Private message"),
 *   token = "private-messages",
 *   message_tokens = {
 *     "message-list": @Translation("List of new private messages")
 *   },
 *   category = "thunder_private_message"
 * )
 */
class PrivateMessage extends NotificationSourceBase {

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    $message = parent::buildMessage();
    if (strpos($message, '{message-list}') === FALSE) {
      return $message;
    }

    $message_list = [];
    foreach ($this->getData() as $entity_info) {
      $message_list[] = $entity_info['url'];
    }
    return strtr($message, ['{message-list}' => ' - ' . implode("\n - ", $message_list)]);
  }

}
