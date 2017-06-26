<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for notification sources.
 */
interface NotificationSourceInterface extends ContainerFactoryPluginInterface {

  /**
   * Return the data provided by the notification source.
   *
   * @return array
   *   Notification source data.
   */
  public function getData();

  /**
   * Set the source data.
   *
   * @param array $data
   *   Data of the notification source.
   */
  public function setData(array $data);

  /**
   * Build the source-specific message.
   *
   * @return string
   *   The message provided by the notification source.
   */
  public function buildMessage();

  /**
   * Check if source is still valid and can be added to notifications.
   *
   * @return bool
   *   TRUE if source is valid, FALSE otherwise.
   */
  public function isValid();

}
