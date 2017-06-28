<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Notification manager.
 */
class NotificationManager implements NotificationManagerInterface {

  use StringTranslationTrait;

  /**
   * The notification storage.
   *
   * @var \Drupal\thunder_notify\NotificationStorageInterface
   */
  protected $storage;

  /**
   * The notification source manager.
   *
   * @var \Drupal\thunder_notify\NotificationSourceManagerInterface
   */
  protected $sourceManager;

  /**
   * The notification type manager.
   *
   * @var \Drupal\thunder_notify\NotificationTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The notification queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The notification queue.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManagerInterface
   */
  protected $queueManager;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Creates a new NotificationManager object.
   *
   * @param \Drupal\thunder_notify\NotificationStorageInterface $storage
   *   Notification storage.
   * @param \Drupal\thunder_notify\NotificationSourceManagerInterface $source_manager
   *   Notification source manager.
   * @param \Drupal\thunder_notify\NotificationTypeManagerInterface $type_manager
   *   Notification type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queue_worker_manager
   *   The queue worker manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(NotificationStorageInterface $storage, NotificationSourceManagerInterface $source_manager, NotificationTypeManagerInterface $type_manager, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager, QueueFactory $queue_factory, QueueWorkerManagerInterface $queue_worker_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->storage = $storage;
    $this->sourceManager = $source_manager;
    $this->typeManager = $type_manager;
    $this->configFactory = $config_factory;
    $this->userStorage = $entity_manager->getStorage('user');
    $this->queue = $queue_factory->get('thunder_notify');
    $this->queueManager = $queue_worker_manager;
    $this->logger = $logger_factory->get('thunder_notify');
  }

  /**
   * {@inheritdoc}
   */
  public function collect() {
    $message_list = $this->getMessages();

    /* @var $recipients \Drupal\Core\Entity\EntityInterface[] */
    $recipients = $this->userStorage->loadMultiple(array_keys($message_list));
    foreach ($message_list as $uid => $message_groups) {
      foreach ($message_groups as $messages) {
        // Save notification with all messages within this group to queue.
        $queue_item = new \stdClass();
        $queue_item->user = $recipients[$uid];
        $queue_item->messages = $messages;
        $this->queue->createItem($queue_item);
      }
    }
    // @todo: Delete notifications sent to queue.
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    $config = $this->configFactory->get('thunder_notify.settings');
    // Send messages from queue.
    $this->logger->info($this->formatPlural($this->queue->numberOfItems(), 'Processing notification queue. The queue contains 1 item', 'Processing notification queue. The queue contains @count items.'));
    /* @var $queue_worker \Drupal\thunder_notify\Plugin\QueueWorker\NotificationQueueWorker */
    $queue_worker = $this->queueManager->createInstance('notification_sender');
    // Limit worker to a fixed number of items.
    $limit = $config->get('queue.limit') ?: 100;
    $process = 0;
    while (($item = $this->queue->claimItem()) && ($process <= $limit)) {
      try {
        $process++;
        $queue_worker->processItem($item->data);
        $this->queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        $this->queue->releaseItem($item);
        break;
      }
      catch (\Exception $e) {
        watchdog_exception('thunder_notify', $e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalTokens() {
    $tokens = [
      '{site:name}' => $this->configFactory->get('system.site')->get('name'),
      '{site:url}' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];
    return $tokens;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserTokens(EntityInterface $user) {
    // Use scalar values only.
    $tokens = array_filter($user->toArray(), function ($value) {
      return isset($value[0]['value']) && is_scalar($value[0]['value']);
    });
    // Flatten array.
    array_walk($tokens, function (&$item) {
      $item = $item[0]['value'];
    });
    // Remove password for security reasons.
    unset($tokens['pass']);

    return array_combine(array_map(function ($key) {
      return "{user:{$key}}";
    }, array_keys($tokens)), $tokens);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    /* @var $notification_sources \Drupal\thunder_notify\NotificationSourceInterface[] */
    $notification_sources = $this->sourceManager->getGroupedInstances();

    $messages = [];
    foreach ($notification_sources as $category => $sources) {
      // List all notifications for the source ids.
      $notifications = $this->storage->loadByProperties(['source' => array_keys($sources)]);
      foreach ($notifications as $notification) {
        if (!isset($sources[$notification['source']])) {
          continue;
        }
        /* @var $source \Drupal\thunder_notify\NotificationSourceInterface */
        $source = $sources[$notification['source']];

        // Set data so it can be processed by the notification source.
        $source->setData($notification['data']);
        $messages[$notification['uid']][$category][] = $source->buildMessage();
      }
    }

    return $messages;
  }

}
