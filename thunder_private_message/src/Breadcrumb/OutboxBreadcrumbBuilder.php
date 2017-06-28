<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for private messages in outbox.
 */
class OutboxBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new OutboxBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return ('entity.user.thunder_private_message.outbox' === $route_match->getRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    // Add link hierarchy.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->currentUser->getDisplayName(), 'entity.user.canonical', ['user' => $this->currentUser->id()]));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Private messages'), 'entity.user.thunder_private_message.inbox', ['user' => $this->currentUser->id()]));

    // Set up cache metadata.
    $breadcrumb->addCacheContexts(['url', 'user']);

    return $breadcrumb;
  }

}
