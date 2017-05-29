<?php

namespace Drupal\thunder_private_message\Plugin\views\access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\access\AccessPluginBase;
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

  /**
   * {@inheritdoc}
   */
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
    return ['user.permissions', 'user'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

}
