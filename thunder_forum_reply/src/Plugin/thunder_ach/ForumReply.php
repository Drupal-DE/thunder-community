<?php

namespace Drupal\thunder_forum_reply\Plugin\thunder_ach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\thunder_ach\Plugin\ThunderAccessControlHandlerBase;
use Drupal\thunder_forum_reply\ForumReplyStorageInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// @todo Rewrite to use new pACH module.

/**
 * Default access control handler plugin for forum replies.
 *
 * @see \Drupal\thunder_forum_reply\ForumReplyAccessControlHandler
 *
 * @ThunderAccessControlHandler(
 *   id = "thunder_forum_reply",
 *   type = "thunder_forum_reply",
 *   weight = -10
 * )
 */
class ForumReply extends ThunderAccessControlHandlerBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, NodeStorageInterface $node_storage, ForumReplyStorageInterface $forum_reply_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->forumReplyStorage = $forum_reply_storage;
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('entity_type.manager')->getStorage('thunder_forum_reply')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // Applies to all forum replies.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // TODO ForumReply::checkAccess()
    return AccessResult::allowed();

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'access content');

      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ["edit terms in {$entity->bundle()}", 'administer taxonomy'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ["delete terms in {$entity->bundle()}", 'administer taxonomy'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $access = AccessResult::allowedIfHasPermission($account, 'create forum replies');

    // Invalid forum node context.
    /** @var \Drupal\node\NodeInterface $node */
    if (empty($context['nid']) || !($node = $this->nodeStorage->load($context['nid']))) {
      return AccessResult::forbidden('Invalid forum node context');
    }

    // No forum node access.
    elseif (!$node->access('view', $account)) {
      return AccessResult::forbiddenIf('No view access for forum node')
        ->addCacheableDependency($node);
    }

    // Invalid field context.
    if (empty($context['field_name']) || !$node->hasField($context['field_name'])) {
      return AccessResult::forbidden('Invalid forum reply field name')
        ->addCacheableDependency($node);
    }

    // Forum replies are hidden/closed?
    if ((int) $node->get($context['field_name'])->status !== ForumReplyItemInterface::OPEN) {
      $access = AccessResult::forbidden('Forum replies are hidden/closed');
    }

    // Has parent forum reply?
    if (isset($context['pfrid'])) {
      // Invalid parent forum reply context.
      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $parent */
      if (!($parent = $this->forumReplyStorage->load($context['pfrid']))) {
        $access = AccessResult::forbidden('Parent forum reply not found');
      }

      // Parent forum reply belongs to other forum node.
      elseif ($parent->getRepliedNodeId() !== $node->id()) {
        $access = AccessResult::forbidden('Parent forum reply belongs to other forum node');
      }

      // No parent forum reply access.
      elseif (!$parent->access('view', $account)) {
        $access = AccessResult::forbidden('No view access for parent forum reply');
      }
    }

    // Add forum node to cache dependencies (if exists).
    if (isset($node)) {
      $access->addCacheableDependency($node);
    }

    // Add parent forum reply to cache dependencies (if exists).
    if (isset($parent)) {
      $access->addCacheableDependency($parent);
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    // TODO ForumReply::checkFieldAccess()
    if ($operation === 'edit') {
      // Status field is only visible for forum administrators.
      if ($field_definition->getName() === 'status') {
        return AccessResult::allowedIfHasPermission($account, 'administer forums')
          ->cachePerPermissions();
      }
    }

    return AccessResult::allowed()
      ->cachePerPermissions();

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
