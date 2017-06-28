<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Breadcrumb builder for private messages in outbox.
 */
class PrivateMessageInOutboxBreadcrumbBuilder extends PrivateMessageBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    /* @var $message \Drupal\message\MessageInterface */
    $message = $route_match->getParameter('message');

    return ('entity.message.canonical.thunder_private_message' === $route_match->getRouteName() && 'outbox' === $route_match->getParameter('message_directory')) && !empty($message) && ('thunder_private_message' === $message->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = parent::build($route_match);

    if ($this->message) {
      if (($sender = $this->message->getOwner())) {
        $breadcrumb->addLink(Link::createFromRoute($sender->getDisplayName(), 'entity.user.canonical', [
          'user' => $sender->id(),
        ]));

        $breadcrumb->addLink(Link::createFromRoute($this->t('Private messages'), 'entity.user.thunder_private_message.inbox', [
          'user' => $sender->id(),
        ]));

        $breadcrumb->addLink(Link::createFromRoute($this->t('Outbox'), 'entity.user.thunder_private_message.outbox', [
          'user' => $sender->id(),
        ]));
      }
    }

    return $breadcrumb;
  }

}
