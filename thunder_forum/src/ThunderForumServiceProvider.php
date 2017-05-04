<?php

namespace Drupal\thunder_forum;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

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
    }
  }

}
