<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for notification managers.
 */
interface NotificationManagerInterface {

  /**
   * Collect notifications to send.
   *
   * Notifications with sources having the same category will be combined into
   * one message.
   */
  public function collect();

  /**
   * Send all pending notifications.
   */
  public function send();

  /**
   * Get list of messages to send.
   *
   * @return array
   *   List of messages grouped by user ID and source category.
   */
  public function getMessages();

  /**
   * Get a list of global tokens to replace in the messages.
   *
   * @return array
   *   List of tokens to replace, keyed by token.
   */
  public function getGlobalTokens();

  /**
   * Get tokens for a specific user object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user object.
   *
   * @return array
   *   List of user tokens.
   */
  public function getUserTokens(EntityInterface $user);

}
