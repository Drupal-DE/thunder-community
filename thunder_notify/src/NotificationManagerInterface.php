<?php

namespace Drupal\thunder_notify;

/**
 * Interface for notification managers.
 */
interface NotificationManagerInterface {

  /**
   * Send all pending notifications.
   *
   * Notifications with sources having the same category will be combined into
   * one message.
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

}
