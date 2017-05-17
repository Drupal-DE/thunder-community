<?php

namespace Drupal\thunder_private_message\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Access plugin that provides user and permission-based access control.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "thunder_message_private_list_owner",
 *   title = @Translation("Private message: list owner"),
 *   help = @Translation("Grant access for message list owners and users with proper permissions.")
 * )
 */
class MessagelistOwnerOrAdmin extends AccessPluginBase implements CacheableDependencyInterface {

  /**
   * Constructs a InboxPermission object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return thunder_private_message_list_access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', 'thunder_private_message_list_access');
  }

  public function summaryTitle() {
    return $this->t('Messagelist owner or admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user.permissions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
