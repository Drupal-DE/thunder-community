<?php

namespace Drupal\thunder_private_message;

/**
 * Interface for private message #lazy_builder callbacks services.
 */
interface PrivateMessageLazyBuilderInterface {

  /**
   * Lazy builder callback; render icon.
   *
   * @param int $mid
   *   The message ID.
   *
   * @return array
   *   A renderable array containing the icon.
   */
  public function renderIcon($mid);

  /**
   * Lazy builder callback; builds a private message's links.
   *
   * @param string $message_entity_id
   *   The message entity ID.
   * @param string $view_mode
   *   The view mode in which the message entity is being viewed.
   * @param string $langcode
   *   The language in which the message entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the message entity is currently being previewed.
   * @param string|null $location
   *   An optional links location (e.g. to split up message links to several
   *   link lists).
   *
   * @return array
   *   A renderable array representing the message links.
   */
  public function renderLinks($message_entity_id, $view_mode, $langcode, $is_in_preview, $location = NULL);

  /**
   * Lazy builder callback; render number of unread messages of a user.
   *
   * @param int $uid
   *   The user ID to render the unread messages count for.
   *
   * @return array
   *   A renderable array containing the unread messages count.
   */
  public function renderUnreadCount($uid);

}
