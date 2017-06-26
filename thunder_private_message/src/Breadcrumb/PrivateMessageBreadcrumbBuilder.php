<?php

namespace Drupal\thunder_private_message\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;

/**
 * Base breadcrumb builder for private messages.
 */
abstract class PrivateMessageBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The message.
   *
   * @var \Drupal\message\MessageInterface
   */
  protected $message;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a new PrivateMessageBreadcrumbBuilder.
   *
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   */
  public function __construct(PrivateMessageHelperInterface $private_message_helper) {
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    /* @var $message \Drupal\message\MessageInterface */
    if (!($this->message = $route_match->getParameter('message'))) {
      // Something went wrong here.
      return $breadcrumb;
    }

    $breadcrumb->addCacheContexts(['url', 'user']);
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    return $breadcrumb;
  }

}
