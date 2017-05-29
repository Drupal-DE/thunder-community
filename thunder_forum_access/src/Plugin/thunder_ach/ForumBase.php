<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\thunder_ach\Plugin\ThunderAccessControlHandlerBase;
use Drupal\thunder_forum\ThunderForumManagerInterface;
use Drupal\thunder_forum_access\Access\ForumAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

// @todo Rewrite to use new pACH module.

/**
 * Provides a base class for forum access control handlers.
 */
abstract class ForumBase extends ThunderAccessControlHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The forum access manager.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessManagerInterface
   */
  protected $forumAccessManager;

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThunderForumManagerInterface $forum_manager, ForumAccessManagerInterface $forum_access_manager, RouteMatchInterface $route_match, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->forumAccessManager = $forum_access_manager;
    $this->forumManager = $forum_manager;
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('forum_manager'),
      $container->get('thunder_forum_access.forum_access_manager'),
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

}
