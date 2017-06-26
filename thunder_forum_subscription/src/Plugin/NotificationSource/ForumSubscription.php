<?php

namespace Drupal\thunder_forum_subscription;

use Drupal\thunder_notify\NotificationSourceBase;

/**
 * Notification source for forum subscriptions.
 *
 * @NotificationSouce(
 *   id = "forum_subscription",
 *   label = @Translation("Forum subscription"),
 *   group = "thunder_forum_subscriptions"
 * )
 */
class ForumSubscription extends NotificationSourceBase {

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    parent::isValid();
  }

}
