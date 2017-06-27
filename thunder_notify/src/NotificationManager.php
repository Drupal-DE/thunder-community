<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Notification manager.
 */
class NotificationManager implements NotificationManagerInterface {

  /**
   * The notification storage.
   *
   * @var \Drupal\thunder_notify\NotificationStorage
   */
  protected $storage;

  /**
   * The notification source manager.
   *
   * @var \Drupal\thunder_notify\NotificationSourceManager
   */
  protected $sourceManager;

  /**
   * The notification type manager.
   *
   * @var \Drupal\thunder_notify\NotificationTypeManager
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
   */
  public function __construct(NotificationStorageInterface $storage, NotificationSourceManagerInterface $source_manager, NotificationTypeManagerInterface $type_manager, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_manager) {
    $this->storage = $storage;
    $this->sourceManager = $source_manager;
    $this->typeManager = $type_manager;
    $this->configFactory = $config_factory;
    $this->userStorage = $entity_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    /* @var $notification_types \Drupal\thunder_notify\NotificationTypeInterface[] */
    $notification_types = $this->typeManager->getInstances(TRUE);

    $message_list = $this->getMessages();

    /* @var $recipients \Drupal\Core\Entity\EntityInterface[] */
    $recipients = $this->userStorage->loadMultiple(array_keys($message_list));
    foreach ($message_list as $uid => $message_groups) {
      $replacements = $this->getGlobalTokens() + $this->getUserTokens($recipients[$uid]);
      foreach ($message_groups as $messages) {
        // Send a single notification with all messages within this group.
        foreach ($notification_types as $type) {
          $type->send($messages, $replacements);
        }
      }
    }

    // @todo: Delete sent notifications from DB.
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
   * Get tokens for a specific user object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $user
   *   The user object.
   *
   * @return array
   *   List of user tokens.
   */
  protected function getUserTokens(EntityInterface $user) {
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
