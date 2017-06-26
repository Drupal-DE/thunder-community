<?php

namespace Drupal\thunder_notify;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for notification sources.
 */
class NotificationSourceManager extends DefaultPluginManager implements NotificationSourceManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }

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
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/NotificationType', $namespaces, $module_handler, 'Drupal\thunder_notify\NotificationTypeInterface', 'Drupal\thunder_notify\Annotation\NotificationType');

    $this->alterInfo('thunder_notify_types');
    $this->setCacheBackend($cache_backend, 'thunder_notify_types');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    // Ensure that every plugin has a category and a weight.
    if (empty($definition['category'])) {
      $definition['category'] = 'default';
    }
    if (empty($definition['weight'])) {
      $definition['weight'] = 0;
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
  public function save(NotificationSourceInterface $source) {
    // NotificationStorage::save().
  }

}
