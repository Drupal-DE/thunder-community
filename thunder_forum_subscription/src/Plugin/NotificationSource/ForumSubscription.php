<?php

namespace Drupal\thunder_forum_subscription\Plugin\NotificationSource;

use Drupal\thunder_notify\NotificationSourceBase;

/**
 * Notification source for forum subscriptions.
 *
 * @NotificationSource(
 *   id = "forum_subscription",
 *   label = @Translation("Forum subscription"),
 *   token = "forum-subscriptions",
 *   message_tokens = {
 *     "forum-list": @Translation("List of forums with new content.")
 *   },
 *   category = "thunder_forum_subscription"
 * )
 */
class ForumSubscription extends NotificationSourceBase {

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    // @todo: check if necessary.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    $message = parent::buildMessage();
    if (strpos($message, '{forum-list}') === FALSE) {
      return $message;
    }

    $forum_list = [];
    foreach ($this->getData() as $entity_info) {
      $forum_list[] = $entity_info['url'];
    }
    return strtr($message, ['{forum-list}' => ' - ' . implode("\n - ", $forum_list)]);
  }

}
