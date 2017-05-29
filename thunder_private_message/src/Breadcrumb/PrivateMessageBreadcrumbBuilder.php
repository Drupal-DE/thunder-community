<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for private messages.
 */
class PrivateMessageBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs the PrivateMessageBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account) {
    $this->configFactory = $config_factory;
    $this->account = $account;
  }

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

    $message_author = $message->getOwner();
    /* @var $message_recipient \Drupal\user\UserInterface */
    $message_recipient = $message->get('tpm_recipient')->first()->entity;
    if ($this->account->id() === $message_author->id()) {
      // Author displays message. Set trail to author.
      $this->account = $message_author;
      $show_outbox = TRUE;
    }
    elseif ($this->account->id() === $message_recipient->id()) {
      // Recipient displays message. Set trail to recipient.
      $this->account = $message_recipient;
    }

    // Add link hierarchy.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Front page'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->account->getDisplayName(), 'entity.user.canonical', ['user' => $this->account->id()]));

    // Add link to message inbox/outbox.
    $breadcrumb->addLink(Link::createFromRoute($this->configFactory->get('views.view.private_messages')->get('display.inbox.display_options.title'), 'view.private_messages.inbox', ['user' => $this->account->id()]));
    if ($show_outbox) {
      $breadcrumb->addLink(Link::createFromRoute($this->configFactory->get('views.view.private_messages')->get('display.outbox.display_options.title'), 'view.private_messages.outbox', ['user' => $this->account->id()]));
    }

    $breadcrumb->addCacheContexts(['url', 'user']);

    return $breadcrumb;
  }

}
