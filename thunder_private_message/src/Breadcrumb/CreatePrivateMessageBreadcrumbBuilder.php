<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for 'Create private message'.
 */
class CreatePrivateMessageBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CreatePrivateMessageBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(AccountInterface $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return 'thunder_private_message.add' === $route_match->getRouteName();
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
