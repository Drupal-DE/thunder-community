<?php

namespace Drupal\thunder_notify;

/**
 * Interface for notification source manager.
 */
interface NotificationSourceManagerInterface {

  /**
   * Get list of notification source plugin instances.
   *
   * @return \Drupal\thunder_notify\NotificationSourceInterface[]
   *   List of notification source plugins.
   */
  public function getInstances();

  /**
   * Get grouped list of notification source plugin instances.
   *
   * @return \Drupal\thunder_notify\NotificationSourceInterface[]
   *   Grouped list of notification source plugins.
   */
  public function getGroupedInstances();

}
