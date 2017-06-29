<?php

namespace Drupal\thunder_notify;

/**
 * Interface for notification type manager.
 */
interface NotificationTypeManagerInterface {

  /**
   * Get list of notification type plugin instances.
   *
   * @param bool $only_enabled
   *   If set to TRUE, only enabled plugin instances will be returned.
   *
   * @return \Drupal\thunder_notify\NotificationTypeInterface[]
   *   List of (enabled) notification type plugins.
   */
  public function getInstances($only_enabled = FALSE);

}
