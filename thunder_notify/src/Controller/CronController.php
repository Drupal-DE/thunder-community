<?php

namespace Drupal\thunder_notify\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\thunder_notify\NotificationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom cron controller for notifications.
 *
 * @package Drupal\thunder_notify\Controller
 */
class CronController extends ControllerBase {

  /**
   * The notification manager.
   *
   * @var \Drupal\thunder_notify\NotificationManager
   */
  protected $notificationManager;

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(NotificationManager $manager, ImmutableConfig $config) {
    $this->notificationManager = $manager;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      \Drupal::service('thunder_notify.notification.manager'),
      \Drupal::config('thunder_notify.settings')
    );
  }

  /**
   * Send pending notifications.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   RedirectResponse.
   */
  public function sendNotifications() {
    $this->notificationManager->send();

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
    $valid_cron_key = $this->config->get('cron_access_key');
    return AccessResult::allowedIf($valid_cron_key == $cron_key);
  }

}
