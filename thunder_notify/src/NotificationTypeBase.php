<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base notification type.
 */
abstract class NotificationTypeBase extends PluginBase implements NotificationTypeInterface {

  /**
   * Category to use for the notification type.
   *
   * @var string
   */
  protected $category = 'default';

  /**
   * The type config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setCategory($category) {
    $this->category = $category;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    // Try loading the requested category configuration.
    $config = $this->configFactory->get("thunder_notify.type.{$this->getPluginId()}.{$this->category}");
    if ('default' !== $this->category && empty($config->getOriginal())) {
      // Configuration for this category does not exists; use default.
      return $this->configFactory->get("thunder_notify.type.{$this->getPluginId()}.default");
    }
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMessage() {
    return $this->getConfig()->get('message');
  }

  /**
   * {@inheritdoc}
   */
  public function buildSubject() {
    return $this->getConfig()->get('subject');
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $messages, array $replacements = []) {
    return TRUE;
  }

}
