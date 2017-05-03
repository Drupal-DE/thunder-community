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
    if ($route = $collection->get('forum.page')) {
      $requirements = [
        '_entity_access' => 'taxonomy_term.view',
        'taxonomy_term' => '\d+',
      ];
      $route->setRequirements($requirements);
    }
  }

}
