<?php

namespace Drupal\thunder_private_message\PathProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes inbound/outbound private message paths.
 */
class PathProcessorPrivateMessage implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a PathProcessorPrivateMessage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateMessageHelperInterface $private_message_helper, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Rewrite private message paths.
    $this->rewritePrivateMessagePath($path);

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Rewrite private message paths.
    $this->rewritePrivateMessagePath($path, $bubbleable_metadata);

    return $path;
  }

  /**
   * Rewrite private message paths.
   *
   * @param string $path
   *   An internal path.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   (optional) Object to collect path processors' bubbleable metadata.
   */
  protected function rewritePrivateMessagePath(&$path, BubbleableMetadata $bubbleable_metadata = NULL) {
    $pattern = '!^/message/(\d+)$!';
    $matches = [];

    // Is message path?
    if (preg_match($pattern, $path, $matches) && !empty($matches[1])) {
      /** @var \Drupal\message\MessageInterface $message */
      $message = $this->entityTypeManager
        ->getStorage('message')
        ->load($matches[1]);

      // Is private message?
      if ('thunder_private_message' === $message->bundle()) {
        $recipient = $this->privateMessageHelper->getMessageRecipient($message);

        // Prepare route parameters.
        $route_parameters = [
          'user' => $this->currentUser->id(),
          'message' => $message->id(),
        ];

        // Current user is sender?
        if ($this->currentUser->id() === $message->getOwnerId()) {
          $route_parameters['message_directory'] = 'outbox';
        }

        // Current user is recipient?
        elseif ($recipient && $this->currentUser->id() === $recipient->id()) {
          $route_parameters['message_directory'] = 'inbox';
        }

        // Current user is neither sender nor recipient -> send to message in
        // outbox context.
        else {
          $route_parameters['user'] = $message->getOwnerId();
          $route_parameters['message_directory'] = 'outbox';
        }

        $path = '/' . ltrim(Url::fromRoute('entity.message.canonical.thunder_private_message', $route_parameters)->getInternalPath(), '/');

        // Set up cache data.
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheableDependency($message)
            ->addCacheableDependency($this->currentUser);
        }
      }
    }
  }

}
