<?php

namespace Drupal\thunder_notify\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Provides the NotificationSource configuration entity.
 *
 * @ConfigEntityType(
 *   id = "notification_source",
 *   label = @Translation("Notification source"),
 *   config_prefix = "source",
 *   entity_keys = {
 *     "id" = "id",
 *     "message" = "message"
 *   },
 *   config_export = {
 *     "id",
 *     "message"
 *   }
 * )
 */
class NotificationSource extends ConfigEntityBase {

  /**
   * Message of the notification source.
   *
   * @var string
   */
  protected $message = '';

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
  }

}
