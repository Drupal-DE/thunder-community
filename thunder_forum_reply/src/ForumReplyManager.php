<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Forum reply manager contains common functions to manage forum reply fields.
 */
class ForumReplyManager implements ForumReplyManagerInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Construct the ForumReplyManager object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityManagerInterface $entity_manager, QueryFactory $query_factory, ModuleHandlerInterface $module_handler, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $map = $this->entityManager->getFieldMapByFieldType('thunder_forum_reply');

    return isset($map['node']) ? $map['node'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCountNewReplies(NodeInterface $node, $field_name = NULL, $timestamp = 0) {
    // @todo Replace module handler with optional history service injection
    //   after https://www.drupal.org/node/2081585.
    if ($this->currentUser->isAuthenticated() && $this->moduleHandler->moduleExists('history')) {
      // Retrieve the timestamp at which the current user last viewed this
      // forum node.
      if (!$timestamp) {
        $timestamp = history_read($node->id());
      }

      $timestamp = ($timestamp > HISTORY_READ_LIMIT ? $timestamp : HISTORY_READ_LIMIT);

      // Use the timestamp to retrieve the number of new forum replies.
      $query = $this->queryFactory->get('thunder_forum_reply')
        ->condition('nid', $node->id())
        ->condition('created', $timestamp, '>')
        ->condition('status', ForumReplyInterface::PUBLISHED);

      if ($field_name) {
        // Limit to a particular field.
        $query->condition('field_name', $field_name);
      }

      return $query->count()->execute();
    }

    return FALSE;
  }

}
