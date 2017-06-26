<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base notification type.
 */
abstract class NotificationSourceBase extends PluginBase implements NotificationSourceInterface {

  /**
   * The plugin config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The plugin data.
   *
   * @var array
   */
  protected $data = [];

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
    return new static($configuration, $plugin_id, $plugin_definition, \Drupal::config("thunder.notify.source.{$plugin_id}"));
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    return $this->config->get('message');
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    return TRUE;
  }

}
