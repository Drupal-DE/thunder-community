<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the forum reply entity type.
 *
 * @see \Drupal\thunder_forum_reply\Entity\ForumReply
 */
class ForumReplyAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The forum reply storage.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface
   */
  protected $forumReplyStorage;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new ForumReplyAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\thunder_forum_reply\ForumReplyStorageInterface $forum_reply_storage
   *   The forum reply storage.
   * @param \Drupal\node\NodeStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityTypeInterface $entity_type, ForumReplyStorageInterface $forum_reply_storage, NodeStorageInterface $node_storage) {
    parent::__construct($entity_type);

    $this->forumReplyStorage = $forum_reply_storage;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */

    $field_name = $entity->getFieldName();
    $node = $entity->getRepliedNode();
    $parent = $entity->hasParentReply() ? $entity->getParentReply() : NULL;

    // Perform parent entity access checks.
    if (($access = $this->parentEntityAccessChecks($field_name, $account, $node, $parent))->isForbidden()) {
      return $access;
    }

    // Take parent access checks into account.
    $access = $access->orIf(parent::access($entity, $operation, $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */

    $field_name = $entity->getFieldName();
    $node = $entity->getRepliedNode();
    $parent = $entity->hasParentReply() ? $entity->getParentReply() : NULL;

    // Perform parent entity access checks.
    if (($access = $this->parentEntityAccessChecks($field_name, $account, $node, $parent))->isForbidden()) {
      return $access;
    }

    switch ($operation) {
      case 'view':
        $view_access = AccessResult::allowedIfHasPermission($account, 'access forum replies')
          ->andIf(AccessResult::allowedIf((int) $node->get($field_name)->status !== ForumReplyItemInterface::HIDDEN))
          ->andIf(AccessResult::allowedIf($entity->isPublished())
            ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('view own unpublished forum replies')))
          );

        $access = $access->orIf($view_access);
        break;

      case 'update':
        $update_access = AccessResult::allowedIf($entity->access('view', $account))
          ->andIf(AccessResult::allowedIf((int) $node->get($field_name)->status === ForumReplyItemInterface::OPEN))
          ->andIf(AccessResult::allowedIfHasPermission($account, 'edit forum replies')
            ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('edit own forum replies')))
        );

        $access = $access->orIf($update_access);
        break;

      case 'delete':
        $delete_access = AccessResult::allowedIf($entity->access('view', $account))
          ->andIf(AccessResult::allowedIf((int) $node->get($field_name)->status === ForumReplyItemInterface::OPEN))
          ->andIf(AccessResult::allowedIfHasPermission($account, 'delete forum replies')
            ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('delete own forum replies')))
          );

        $access = $access->orIf($delete_access);
    }

    // Take parent access checks into account.
    $access = $access->orIf(parent::checkAccess($entity, $operation, $account))
      ->cachePerPermissions()
      ->addCacheableDependency($entity);

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $field_name = !empty($context['field_name']) ? $context['field_name'] : NULL;

    /** @var \Drupal\node\NodeInterface $node */
    $node = !empty($context['nid']) ? $this->nodeStorage->load($context['nid']) : NULL;

    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $parent */
    $parent = !empty($context['pfrid']) ? $this->forumReplyStorage->load($context['pfrid']) : NULL;

    // Perform parent entity access checks.
    if (($access = $this->parentEntityAccessChecks($field_name, $account, $node, $parent))->isForbidden()) {
      return $access;
    }

    // User is allowed to create forum replies and forum replies are not
    // hidden/closed?
    $access = $access->orIf(AccessResult::allowedIf(
      $account->hasPermission('create forum replies')
    ));

    // Take parent access checks into account.
    $access = $access->orIf(parent::checkCreateAccess($account, $context, $entity_bundle));

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($operation === 'edit') {
      // Status field is only visible for forum administrators.
      if ($field_definition->getName() === 'status') {
        return AccessResult::allowedIfHasPermission($account, 'administer forums')
          ->cachePerPermissions();
      }
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * Perform parent entity access checks.
   *
   * This method performs access checks against a forum reply's parent entities:
   *   - Parent forum node (required)
   *   - Parent forum reply (optional)
   *
   * @param string $field_name
   *   The field_name to which the forum reply belongs.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to use for access checks.
   * @param \Drupal\node\NodeInterface|null $node
   *   The forum node to which the forum reply belongs.
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface|null $parent
   *   An optional parent forum reply to which the forum reply belongs.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function parentEntityAccessChecks($field_name, AccountInterface $account, NodeInterface $node = NULL, ForumReplyInterface $parent = NULL) {
    $access = AccessResult::neutral();

    // Invalid forum node context.
    if (!$node) {
      $access = AccessResult::forbidden('Invalid forum node context');
    }

    // No forum node 'view' access.
    elseif (!$node->access('view', $account)) {
      $access = AccessResult::forbidden('No view access for forum node');
    }

    // Invalid forum reply field context.
    elseif (!$field_name || !$node->hasField($field_name)) {
      $access = AccessResult::forbidden('Invalid forum reply field context');
    }

    elseif ($parent) {
      // Parent forum reply belongs to other forum node.
      if ($parent->getRepliedNodeId() !== $node->id()) {
        $access = AccessResult::forbidden('Parent forum reply belongs to other forum node');
      }

      // No parent forum reply 'view' access.
      elseif (!$parent->access('view', $account)) {
        $access = AccessResult::forbidden('No view access for parent forum reply');
      }
    }

    // Administrators are always allowed.
    $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'administer forums'))
      ->cachePerPermissions();

    // Add forum node to access result cache (if any).
    if ($node) {
      $access->addCacheableDependency($node);
    }

    // Add parent forum reply to access result cache (if any).
    if ($parent) {
      $access->addCacheableDependency($parent);
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type,
      $entity_type_manager->getStorage('thunder_forum_reply'),
      $entity_type_manager->getStorage('node')
    );
  }

}
