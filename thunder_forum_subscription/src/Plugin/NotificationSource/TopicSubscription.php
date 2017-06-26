<?php

namespace Drupal\thunder_forum_subscription;

use Drupal\thunder_notify\NotificationSourceBase;

/**
 * Notification source for forum topic subscriptions.
 *
 * @NotificationSouce(
 *   id = "topic_subscription",
 *   label = @Translation("Topic subscription"),
 *   group = "thunder_forum_subscriptions"
 * )
 */
class TopicSubscription extends NotificationSourceBase {

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    parent::isValid();
  }

}
