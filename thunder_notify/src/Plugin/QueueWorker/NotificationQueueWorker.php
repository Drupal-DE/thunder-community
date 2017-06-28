<?php

namespace Drupal\thunder_notify\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\thunder_notify\NotificationManagerInterface;
use Drupal\thunder_notify\NotificationStorageInterface;
use Drupal\thunder_notify\NotificationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * QueueWorker to send notifications using a custom cron.
 *
 * @QueueWorker(
 *   id = "notification_sender",
 *   title = @Translation("Notification sender"),
 * )
 */
class NotificationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The notification storage.
   *
   * @var \Drupal\thunder_notify\NotificationStorageInterface
   */
  protected $notificationStorage;

  /**
   * The notification manager.
   *
   * @var \Drupal\thunder_notify\NotificationManagerInterface
   */
  protected $notificationManager;

  /**
   * The notification type manager.
   *
   * @var \Drupal\thunder_notify\NotificationTypeManagerInterface
   */
  protected $typeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NotificationStorageInterface $notification_storage, NotificationManagerInterface $notification_manager, NotificationTypeManagerInterface $type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->notificationStorage = $notification_storage;
    $this->notificationManager = $notification_manager;
    $this->typeManager = $type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('thunder_notify.notification.storage'), $container->get('thunder_notify.notification.manager'), $container->get('thunder_notify.notification.manager.type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /* @var $notification_types \Drupal\thunder_notify\NotificationTypeInterface[] */
    $notification_types = $this->typeManager->getInstances(TRUE);
    // Token replacements.
    $replacements = $this->notificationManager->getGlobalTokens() + $this->notificationManager->getUserTokens($data->user);
    foreach ($notification_types as $type) {
      $type->send($data->messages, $replacements);
    }
  }

}
