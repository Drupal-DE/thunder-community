<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for notification types.
 */
interface NotificationTypeInterface extends ContainerFactoryPluginInterface {

  /**
   * Send the notification.
   *
   * @param array $messages
   *   List of messages from notification sources.
   * @param array $replacements
   *   List of tokens to replace in the message.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function send(array $messages, array $replacements = []);

}
