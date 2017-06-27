<?php

namespace Drupal\thunder_notify\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for notification sources.
 *
 * Plugin Namespace: Plugin\NotificationSource.
 *
 * For a working example, see
 * \Drupal\thunder_notify\Plugin\NotificationSource\Email.
 *
 * @see \Drupal\thunder_notify\NotificationSourceManager
 * @see \Drupal\thunder_notify\Plugin\NotificationSourceInterface
 * @see \Drupal\thunder_notify\Plugin\NotificationSourceBase
 * @see plugin_api
 *
 * @Annotation
 */
class NotificationSource extends Plugin {

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
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

  /**
   * The plugin category.
   *
   * Used to group sources within a single notification.
   *
   * @var string
   */
  public $category = 'default';

}
