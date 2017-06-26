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
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function send();

}
