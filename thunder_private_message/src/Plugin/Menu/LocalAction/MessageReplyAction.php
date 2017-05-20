<?php

namespace Drupal\thunder_private_message\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\message\MessageInterface;

/**
 * Local action plugin for message replies.
 */
class MessageReplyAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $request = \Drupal::request();
    /* @var $message \Drupal\message\MessageInterface */
    if (!$request->attributes->has('message') || !(($message = $request->attributes->get('message')) instanceof MessageInterface)) {
      // This should never happen.
      return $options;
    }
    $options['query']['message'] = $message->id();

    return $options;
  }

}
