<?php

namespace Drupal\thunder_forum_subscription\Plugin\NotificationSource;

use Drupal\thunder_notify\NotificationSourceBase;

/**
 * Notification source for forum topic subscriptions.
 *
 * @NotificationSource(
 *   id = "topic_subscription",
 *   label = @Translation("Topic subscription"),
 *   token = "topic-subscriptions",
 *   message_tokens = {
 *     "topic-list": @Translation("List of topics with new replies")
 *   },
 *   category = "thunder_forum_subscription"
 * )
 */
class TopicSubscription extends NotificationSourceBase {

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    $message = parent::buildMessage();
    if (strpos($message, '{topic-list}') === FALSE) {
      return $message;
    }

    $topic_list = [];
    foreach ($this->getData() as $entity_info) {
      $topic_list[] = $entity_info['url'];
    }
    return strtr($message, ['{topic-list}' => ' - ' . implode("\n - ", $topic_list)]);
  }

}
