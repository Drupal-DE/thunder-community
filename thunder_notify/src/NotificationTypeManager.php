<?php

namespace Drupal\thunder_notify;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for notification types.
 */
class NotificationTypeManager extends DefaultPluginManager implements FallbackPluginManagerInterface, NotificationTypeManagerInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new \Drupal\thunder_notify\NotificationTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/NotificationType', $namespaces, $module_handler, 'Drupal\thunder_notify\NotificationTypeInterface', 'Drupal\thunder_notify\Annotation\NotificationType');

    $this->alterInfo('thunder_notify_types');
    $this->setCacheBackend($cache_backend, 'thunder_notify_types');

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'logger';
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances($only_enabled = FALSE) {
    $instances = &drupal_static(__FUNCTION__, []);

    if (empty($instances)) {
      // Create instances of all plugins.
      foreach ($this->getDefinitions() as $plugin_id => $definition) {
        $instances[$plugin_id] = $this->createInstance($plugin_id, $definition);
      }
    }

    if (!$only_enabled) {
      return $instances;
    }

    $types_enabled = $this->configFactory->get('thunder_notify.settings')->get('notification_types') ?: [];

    // Return only enabled plugins.
    return array_intersect_key($instances, array_filter($types_enabled));
  }

}
