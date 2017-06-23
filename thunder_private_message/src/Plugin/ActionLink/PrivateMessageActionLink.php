<?php

namespace Drupal\thunder_private_message\Plugin\ActionLink;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\flag\FlagInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Plugin\ActionLink\Reload;
use Drupal\message\MessageInterface;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the private message link type.
 *
 * This class is an extension of the Reload link type, but modified to
 * provide correct destination query parameters.
 *
 * @ActionLinkType(
 *   id = "thunder_private_message_link",
 *   label = @Translation("Normal link (Private message)"),
 *   description = "A private message link with appropriate destination."
 * )
 */
class PrivateMessageActionLink extends Reload {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PrivateMessageActionLink.
   *
   * @param array $configuration
   *   The configuration array with which to initialize this plugin.
   * @param string $plugin_id
   *   The ID with which to initialize this plugin.
   * @param array $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, AccountInterface $current_user, FlagServiceInterface $flag_service, RouteMatchInterface $route_match, PrivateMessageHelperInterface $private_message_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $current_user);

    $this->flagService = $flag_service;
    $this->privateMessageHelper = $private_message_helper;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('flag'),
      $container->get('current_route_match'),
      $container->get('thunder_private_message.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDestination() {
    $message = NULL;
    $back_to_message_page = FALSE;

    // Coming from message page.
    if (($message_from_route_match = $this->routeMatch->getParameter('message')) && $message_from_route_match instanceof MessageInterface) {
      $message = $message_from_route_match;
    }

    // Coming from unflag route.
    elseif (($flag = $this->routeMatch->getParameter('flag')) && $flag instanceof FlagInterface && ($entity_id = $this->routeMatch->getParameter('entity_id'))) {
      $message = $this->flagService->getFlaggableById($flag, $entity_id);
      $back_to_message_page = TRUE;
    }

    // Message context found?
    if ($message && 'thunder_private_message' === $message->bundle()) {
      $route_name = NULL;

      $route_parameters = [
        'user' => $this->currentUser->id(),
      ];

      if ($back_to_message_page) {
        $route_parameters['message'] = $message->id();
      }

      // Current user is sender?
      if ($this->privateMessageHelper->userIsSender($this->currentUser, $message)) {
        $route_name = $back_to_message_page ? 'entity.message.canonical.thunder_private_message.outbox' : 'entity.user.thunder_private_message.outbox';
      }

      // Current user is recipient?
      elseif ($this->privateMessageHelper->userIsRecipient($this->currentUser, $message)) {
        $route_name = $back_to_message_page ? 'entity.message.canonical.thunder_private_message.inbox' : 'entity.user.thunder_private_message.inbox';
      }

      if ($route_name) {
        return Url::fromRoute($route_name, $route_parameters)->toString();
      }
    }

    return parent::getDestination();
  }

}
