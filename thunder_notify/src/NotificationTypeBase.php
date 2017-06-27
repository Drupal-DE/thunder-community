<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base notification type.
 */
abstract class NotificationTypeBase extends PluginBase implements NotificationTypeInterface {

  /**
   * The type config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, \Drupal::config("thunder_notify.type.{$plugin_id}"));
  }

  /**
   * Build the message to send.
   *
   * @return string
   *   The notification message.
   */
  protected function buildMessage() {
    return $this->config->get('message');
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $messages, array $replacements = []) {
    return TRUE;
  }

}
