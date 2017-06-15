<?php

namespace Drupal\thunder_private_message;

/**
 * Interface for private message #lazy_builder callbacks services.
 */
interface PrivateMessageLazyBuilderInterface {

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
