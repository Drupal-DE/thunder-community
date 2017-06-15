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
use Drupal\thunder_forum_reply\Entity\ForumReply;
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

    $account = $this->prepareUser($account);
    $langcode = $entity->language()->getId();

    // Cache hit, no work necessary.
    if (($access = $this->getCache($entity->uuid(), $operation, $langcode, $account)) !== NULL) {
      return $return_as_object ? $access : $access->isAllowed();
    }

    $access = AccessResult::neutral();

    $field_name = $entity->getFieldName();
    $node = $entity->getRepliedNode();
    $parent = $entity->hasParentReply() ? $entity->getParentReply() : NULL;

    // Perform parent entity access checks.
    $access = $access->orIf($this->parentEntityAccessChecks($field_name, $account, $entity, $node, $parent));

    // Take parent access checks into account.
    if (!$access->isForbidden()) {
      $access
        ->orIf(parent::access($entity, $operation, $account, TRUE));
    }

    $access
      // Add forum reply entity to cache dependencies.
      ->addCacheableDependency($entity);

    // Save to cache.
    $this->setCache($access, $entity->uuid(), $operation, $langcode, $account);

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */

    // Take parent access checks into account.
    $access = parent::checkAccess($entity, $operation, $account);

    if (!$access->isForbidden()) {
      $field_name = $entity->getFieldName();
      $node = $entity->getRepliedNode();
      $parent = $entity->hasParentReply() ? $entity->getParentReply() : NULL;

      // Perform parent entity access checks.
      $access = $access->orIf($this->parentEntityAccessChecks($field_name, $account, $entity, $node, $parent));

      // Determine forum reply mode (open/closed/hidden).
      $mode = (int) $node->get($field_name)->status;

      switch ($operation) {
        case 'view':
          $view_access = AccessResult::allowedIfHasPermission($account, 'access forum replies')
            ->andIf(AccessResult::allowedIf($mode !== ForumReplyItemInterface::HIDDEN))
            ->andIf(AccessResult::allowedIf($entity->isPublished())
              ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('view own unpublished forum replies')))
            );

          $access = $access->orIf($view_access);
          break;

        case 'update':
          $update_access = AccessResult::allowedIf($entity->access('view', $account))
            ->andIf(AccessResult::allowedIf($mode === ForumReplyItemInterface::OPEN))
            ->andIf(AccessResult::allowedIfHasPermission($account, 'edit forum replies')
              ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('edit own forum replies')))
            );

          $access = $access->orIf($update_access);
          break;

        case 'delete':
          $delete_access = AccessResult::allowedIf($entity->access('view', $account))
            ->andIf(AccessResult::allowedIf($mode === ForumReplyItemInterface::OPEN))
            ->andIf(AccessResult::allowedIfHasPermission($account, 'delete forum replies')
              ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('delete own forum replies')))
            );

          $access = $access->orIf($delete_access);
          break;
      }
    }

    $access
      // Add forum reply entity to cache dependencies.
      ->addCacheableDependency($entity);

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $entity_bundle);

    if (!$access->isForbidden()) {
      $field_name = !empty($context['field_name']) ? $context['field_name'] : NULL;

      /** @var \Drupal\node\NodeInterface $node */
      $node = !empty($context['nid']) ? $this->nodeStorage->load($context['nid']) : NULL;

      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $parent */
      $parent = !empty($context['pfrid']) ? $this->forumReplyStorage->load($context['pfrid']) : NULL;

      // Create dummy forum reply object.
      $entity = ForumReply::create([
        'field_name' => $field_name,
        'node' => $node ? $node->id() : NULL,
        'pfrid' => $parent ? $parent->id() : NULL,
      ]);

      // Perform parent entity access checks.
      $access = $access->orIf($this->parentEntityAccessChecks($field_name, $account, $entity, $node, $parent));

      if (!$access->isForbidden()) {
        // User is allowed to create forum replies and forum replies are not
        // hidden/closed?
        $access = $access->orIf(AccessResult::allowedIf(
          (int) $node->get($field_name)->status === ForumReplyItemInterface::OPEN,
          $account->hasPermission('create forum replies')
        ));
      }
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $access = parent::checkFieldAccess($operation, $field_definition, $account, $items);

    if (!$access->isForbidden()) {
      if ($operation === 'edit') {
        // Status field is only visible for forum administrators.
        if ($field_definition->getName() === 'status') {
          $access = $access->andIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
        }
      }
    }

    return $access;
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
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $entity
   *   The actual forum reply entity that is being checked.
   * @param \Drupal\node\NodeInterface|null $node
   *   The forum node to which the forum reply belongs.
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface|null $parent
   *   An optional parent forum reply to which the forum reply belongs.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function parentEntityAccessChecks($field_name, AccountInterface $account, ForumReplyInterface $entity, NodeInterface $node = NULL, ForumReplyInterface $parent = NULL) {
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

      else {
        $scanned_parents =& drupal_static(get_class($this) . '::' . __METHOD__ . '::scanned_parents', []);
        $parent_to_check = clone $parent;

        // Traverse up the reply hierarchy to find the first parent reply that
        // does not reflect the current forum reply entity's publishing status
        // (which is the only change to take into account when dealing with view
        // access checking). The result is statically cached for all forum
        // replies on the way up, so no parent has to be determined twice.
        if (!isset($scanned_parents[$entity->getParentReplyId()])) {
          $parent_ids = [$parent_to_check->id()];
          while ($parent_to_check->hasParentReply() && $parent_to_check->isPublished() === $entity->isPublished()) {
            $parent_to_check = $parent_to_check->getParentReply();
            $parent_ids[] = $parent_to_check->id();
          }

          foreach ($parent_ids as $parent_id) {
            $scanned_parents[$parent_id] = $parent_to_check;
          }
        }

        if (!$scanned_parents[$entity->getParentReplyId()]->access('view', $account)) {
          $access = AccessResult::forbidden('No view access for parent forum reply');
        }
      }
    }

    // Administrators are always allowed.
    $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()))
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
