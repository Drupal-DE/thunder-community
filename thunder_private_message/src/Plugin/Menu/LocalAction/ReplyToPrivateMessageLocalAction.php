<?php

namespace Drupal\thunder_private_message\Plugin\Menu\LocalAction;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Local action plugin for 'Create reply'.
 */
class ReplyToPrivateMessageLocalAction extends CreatePrivateMessageLocalAction {

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    $route_parameters = parent::getRouteParameters($route_match);

    /** @var \Drupal\message\MessageInterface $message */
    if (($message = $route_match->getParameter('message'))) {
      $route_parameters['recipient'] = $message->getOwnerId();
      $route_parameters['message'] = $message->id();
    }

    return $route_parameters;
  }

}
