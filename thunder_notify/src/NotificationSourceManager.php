<?php

namespace Drupal\thunder_notify;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for notification sources.
 */
class NotificationSourceManager extends DefaultPluginManager implements CategorizingPluginManagerInterface, NotificationSourceManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new \Drupal\thunder_notify\NotificationSourceManager object.
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
    parent::__construct('Plugin/NotificationSource', $namespaces, $module_handler, 'Drupal\thunder_notify\NotificationSourceInterface', 'Drupal\thunder_notify\Annotation\NotificationSource');

    $this->alterInfo('thunder_notify_sources');
    $this->setCacheBackend($cache_backend, 'thunder_notify_sources');

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Ensure that every plugin has a category and a weight.
    if (empty($definition['weight'])) {
      $definition['weight'] = 0;
    }
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function processDefinitionCategory(&$definition) {
    // Ensure that every plugin has a category.
    if (empty($definition['category'])) {
      $definition['category'] = 'default';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $label_key = 'label') {
    // Sort the plugins first by category, then by weight and label.
    /** @var \Drupal\Core\Plugin\CategorizingPluginManagerTrait|\Drupal\Component\Plugin\PluginManagerInterface $this */
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    uasort($definitions, function ($a, $b) use ($label_key) {
      if ($a['category'] != $b['category']) {
        return strnatcasecmp($a['category'], $b['category']);
      }
      if ($a['weight'] != $b['weight']) {
        return ($a['weight'] < $b['weight']) ? -1 : 1;
      }
      return strnatcasecmp($a[$label_key], $b[$label_key]);
    });
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    $instances = &drupal_static(__FUNCTION__, []);

    if (empty($instances)) {
      // Create instances of all plugins.
      foreach ($this->getSortedDefinitions() as $plugin_id => $definition) {
        $instances[$plugin_id] = $this->createInstance($plugin_id, $definition);
      }
    }

    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupedInstances() {
    $instances = &drupal_static(__FUNCTION__, []);

    if (!empty($instances)) {
      return $instances;
    }

    foreach ($this->getGroupedDefinitions() as $group => $definitions) {
      $instances[$group] = [];
      // Create instances of all plugins.
      foreach ($definitions as $plugin_id => $definition) {
        $instances[$group][$plugin_id] = $this->createInstance($plugin_id, $definition);
      }
    }

    return $instances;
  }

}
