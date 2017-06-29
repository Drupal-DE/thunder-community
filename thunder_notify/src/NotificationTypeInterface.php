<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for notification types.
 */
interface NotificationTypeInterface extends ContainerFactoryPluginInterface {

  /**
   * Set the category to use for the type.
   *
   * @param string $category
   *   The category to use.
   */
  public function setCategory($category);

  /**
   * Get the configuration for the notification type.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The configuration for the type.
   */
  public function getConfig();

  /**
   * Build the message to send.
   *
   * @return string
   *   The notification message.
   */
  public function buildMessage();

  /**
   * Build the message subject.
   *
   * @return string
   *   The notification subject.
   */
  public function buildSubject();

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
