<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base notification type.
 */
abstract class NotificationTypeBase extends PluginBase implements NotificationTypeInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, \Drupal::configFactory());
  }

  /**
   * Build the message to send.
   *
   * @return string
   *   The notification message.
   */
  protected function buildMessage() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function send() {
    return TRUE;
  }

}
