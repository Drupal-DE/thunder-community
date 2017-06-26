<?php

namespace Drupal\thunder_notify\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for notification types.
 *
 * Plugin Namespace: Plugin\NotificationType.
 *
 * For a working example, see
 * \Drupal\thunder_notify\Plugin\NotificationType\Email.
 *
 * @see \Drupal\thunder_notify\NotificationTypeManager
 * @see \Drupal\thunder_notify\Plugin\NotificationTypeInterface
 * @see \Drupal\thunder_notify\Plugin\NotificationTypeBase
 * @see plugin_api
 *
 * @Annotation
 */
class NotificationType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
