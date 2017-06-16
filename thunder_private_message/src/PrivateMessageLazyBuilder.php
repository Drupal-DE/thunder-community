<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a service for private message #lazy_builder callbacks.
 */
class PrivateMessageLazyBuilder implements PrivateMessageLazyBuilderInterface {

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
   * Constructs a new PrivateMessageLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateMessageHelperInterface $private_message_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  public function renderUnreadCount($uid) {
    if (!$uid) {
      return [];
    }

    // Load user account.
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($uid);

    // Load unread messages count.
    $count = $this->privateMessageHelper->getUnreadCount($account);

    // Build unread messages count.
    $build = [
      '#theme' => 'thunder_private_message_unread_count',
      '#unread_count' => $count,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

}
