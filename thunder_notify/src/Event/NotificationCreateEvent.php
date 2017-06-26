<?php

namespace Drupal\thunder_notify\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a notification create event for event listeners.
 */
class NotificationCreateEvent extends Event {

  /**
   * The notification source plugin ID.
   *
   * @var string
   */
  protected $source;

  /**
   * The event data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Constructs an event object.
   *
   * @param string $source
   *   Notification source plugin ID.
   * @param array $data
   *   Data to save (keyed by user ID).
   */
  public function __construct($source, array $data = []) {
    $this->source = $source;
  }

  /**
   * Get the source plugin ID.
   *
   * @return string
   *   The notification source plugin ID.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Get the events data.
   *
   * @return array
   *   The data recorded with the event.
   */
  public function getData() {
    return $this->data;
  }

}
