<?php

namespace Drupal\thunder_notify\Event;

/**
 * Contains all events thrown in "Thunder Notify".
 */
final class NotificationEvents {

  /**
   * The CREATE event occurs every time a new notification should be created.
   *
   * @Event
   *
   * @var string
   */
  const CREATE = 'thunder_notify.create';

}
