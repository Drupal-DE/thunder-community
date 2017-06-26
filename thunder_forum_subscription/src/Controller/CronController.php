<?php

namespace Drupal\thunder_forum_subscription\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom cron controller for notifications.
 *
 * @package Drupal\thunder_forum_subscription\Controller
 */
class CronController extends ControllerBase {

  /**
   * Send pending notifications.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse.
   */
  public function sendNotifications() {
    \Drupal::service('thunder_forum_subscription.notification.manager')->runCron();

    return new Response('', 204);
  }

  /**
   * Checks access.
   *
   * @param string $cron_key
   *   The cron key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access($cron_key) {
    $valid_cron_key = \Drupal::config('thunder_forum_subscription.settings')
      ->get('cron_access_key');
    return AccessResult::allowedIf($valid_cron_key == $cron_key);
  }

}
