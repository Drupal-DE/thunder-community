<?php

namespace Drupal\thunder_notify\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thunder_notify\Event\NotificationCreateEvent;
use Drupal\thunder_notify\Event\NotificationEvents;
use Drupal\thunder_notify\NotificationStorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for notifications provided by "Thunder Notify".
 */
class NotificationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The notification storage.
   *
   * @var \Drupal\thunder_notify\NotificationStorage
   */
  protected $storage;

  /**
   * Construct a new NotificationSubscriber object.
   *
   * @param \Drupal\thunder_notify\NotificationStorageInterface $storage
   *   The notification storage.
   */
  public function __construct(NotificationStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[NotificationEvents::CREATE][] = ['saveNotification', -100];

    return $events;
  }

  /**
   * Save a notification.
   *
   * @param \Drupal\thunder_notify\Event\NotificationCreateEvent $event
   *   The thrown event.
   */
  public function saveNotification(NotificationCreateEvent $event) {
    $event_data = $event->getData();
    /* @var $entity \Drupal\Core\Entity\ContentEntityInterface */
    $entity = $event_data['entity'];
    foreach ($event_data['users'] as $uid) {
      $data = [
        'source' => $event->getSource(),
        'uid' => $uid,
        'data' => [
          $entity->id() => [
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->bundle(),
            'entity_id' => $entity->id(),
            'title' => $entity->label(),
            'url' => $entity->toUrl('canonical', ['absolute' => TRUE])->toString(),
          ],
        ],
      ];
      $this->storage->save($data);
    }
  }

}
