<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for private messages.
 */
class PrivateMessageBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    /* @var $message \Drupal\message\MessageInterface */
    $message = $route_match->getParameter('message');

    return ('entity.message.canonical' === $route_match->getRouteName()) && !empty($message) && ('thunder_private_message' === $message->getTemplate()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    /* @var $message \Drupal\message\MessageInterface */
    $message = $route_match->getParameter('message');
    if (empty($message)) {
      // Something went wrong here.
      return $breadcrumb;
    }

    $show_outbox = FALSE;

    $account = \Drupal::currentUser();
    $message_author = $message->getOwner();
    /* @var $message_recipient \Drupal\user\UserInterface */
    $message_recipient = $message->get('tpm_recipient')->first()->entity;
    if ($account->id() === $message_author->id()) {
      // Author displays message. Set trail to author.
      $account = $message_author;
      $show_outbox = TRUE;
    }
    elseif ($account->id() === $message_recipient->id()) {
      // Recipient displays message. Set trail to recipient.
      $account = $message_recipient;
    }

    // Add link hierarchy.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Front page'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($account->getDisplayName(), 'entity.user.canonical', ['user' => $account->id()]));

    // Add link to message inbox/outbox.
    $breadcrumb->addLink(Link::createFromRoute(\Drupal::config('views.view.private_messages')->get('display.inbox.display_options.title'), 'view.private_messages.inbox', ['user' => $account->id()]));
    if ($show_outbox) {
      $breadcrumb->addLink(Link::createFromRoute(\Drupal::config('views.view.private_messages')->get('display.outbox.display_options.title'), 'view.private_messages.outbox', ['user' => $account->id()]));
    }

    $breadcrumb->addCacheContexts(['url']);

    return $breadcrumb;
  }

}
