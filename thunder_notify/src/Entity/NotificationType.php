<?php

namespace Drupal\thunder_notify\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Provides the NotificationType configuration entity.
 *
 * @ConfigEntityType(
 *   id = "notification_type",
 *   label = @Translation("Notification type"),
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   config_export = {
 *     "id",
 *     "message",
 *     "subject"
 *   }
 * )
 */
class NotificationType extends ConfigEntityBase {

  /**
   * Subject of the notification type.
   *
   * @var string
   */
  protected $subject;

  /**
   * Message of the notification type.
   *
   * @var string
   */
  protected $message = '';

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->subject = $subject;
  }

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
