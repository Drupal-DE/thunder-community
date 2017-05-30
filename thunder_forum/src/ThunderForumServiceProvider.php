<?php

namespace Drupal\thunder_forum;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Custom service provider to alter existing forum services.
 */
class ThunderForumServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override default forum manager.
    if ($container->hasDefinition('forum_manager')) {
      $definition = $container->getDefinition('forum_manager');
      $definition->setClass('Drupal\thunder_forum\ThunderForumManager');

    // Override default forum index storage service to use forum replies instead
    // of comments (if thunder_forum_reply module is enabled).
    if ($container->hasDefinition('forum.index_storage')) {
      $definition = $container->getDefinition('forum.index_storage');
      $definition->setClass('Drupal\thunder_forum\ThunderForumIndexStorage');
      $definition->addArgument(new Reference('module_handler'));
    }
  }

}
