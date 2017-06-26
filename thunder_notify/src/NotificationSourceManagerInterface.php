<?php

namespace Drupal\thunder_notify;

/**
 * Interface for notification source manager.
 */
interface NotificationSourceManagerInterface {

  /**
   * Save a single notification source.
   *
   * @param \Drupal\thunder_notify\NotificationSourceInterface $source
   *   The notification source plugin.
   */
  public function save(NotificationSourceInterface $source);

}
