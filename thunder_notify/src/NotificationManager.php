<?php

namespace Drupal\thunder_notify;

/**
 * Notification manager.
 */
class NotificationManager {

  /**
   * Run the lightweight cron.
   *
   * This function is called from the external crontab job via url
   * /thunder_notify/cron/{access key}.
   */
  public function runCron() {
    // Run implementation of hook_cron().
    thunder_notify_cron();
    if (ob_get_level() > 0) {
      $handlers = ob_list_handlers();
      if (isset($handlers[0]) && $handlers[0] == 'default output handler') {
        ob_clean();
      }
    }
  }

  /**
   * Send all pending notifications.
   */
  public function send() {

  }

}
