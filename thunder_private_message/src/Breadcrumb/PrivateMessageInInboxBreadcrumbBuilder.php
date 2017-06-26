<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Breadcrumb builder for private messages in inbox.
 */
class PrivateMessageInInboxBreadcrumbBuilder extends PrivateMessageBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    /* @var $message \Drupal\message\MessageInterface */
    $message = $route_match->getParameter('message');

    return ('entity.message.canonical.thunder_private_message.inbox' === $route_match->getRouteName()) && !empty($message) && ('thunder_private_message' === $message->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);

    if ($this->message) {
      if (($recipient = $this->privateMessageHelper->getMessageRecipient($this->message))) {
        $breadcrumb->addLink(Link::createFromRoute($recipient->getDisplayName(), 'entity.user.canonical', [
          'user' => $recipient->id(),
        ]));

        $breadcrumb->addLink(Link::createFromRoute($this->t('Private messages'), 'entity.user.thunder_private_message.inbox', [
          'user' => $recipient->id(),
        ]));
      }
    }

    return $breadcrumb;
  }

}
