<?php

namespace Drupal\thunder_forum_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter default forum route.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Forum page.
    if ($route = $collection->get('forum.page')) {
      $requirements = [
        '_thunder_forum_access_forum_term_access' => 'taxonomy_term.view',
        'taxonomy_term' => '\d+',
      ];

      $route->addRequirements($requirements);
    }

    // Edit forum.
    if ($route = $collection->get('entity.taxonomy_term.forum_edit_form')) {
      $requirements = [
        '_thunder_forum_access_forum_term_access' => 'taxonomy_term.update',
        'taxonomy_term' => '\d+',
      ];

      $route->setRequirements($requirements);
    }

    // Edit forum container.
    if ($route = $collection->get('entity.taxonomy_term.forum_edit_container_form')) {
      $requirements = [
        '_thunder_forum_access_forum_term_access' => 'taxonomy_term.update',
        'taxonomy_term' => '\d+',
      ];

      $route->setRequirements($requirements);
    }
  }

}
