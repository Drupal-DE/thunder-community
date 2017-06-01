<?php

namespace Drupal\thunder_forum_access\Plugin\pach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;

/**
 * Provides an access control handler for forum replies.
 *
 * @AccessControlHandler(
 *   id = "thunder_forum_access_thunder_forum_reply",
 *   type = "thunder_forum_reply",
 *   weight = 1
 * )
 */
class ForumReply extends ForumBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */

    // No forum reference available.
    if (!($term = $this->forumManager->getForumTermByNode($entity->getRepliedNode()))) {
      $admin_access = AccessResult::allowedIf(
        $this->forumAccessManager->userIsForumAdmin($account)
      );

      // Grant access only for forum administrators.
      $access = $access->isAllowed() ? $access->andIf($admin_access) : $access->orIf($admin_access);
    }

    // Forum reference found.
    else {
      // Load forum access record.
      $record = $this->forumAccessManager->getForumAccessRecord($term->id());

      // Determine forum reply mode (open/closed/hidden).
      $mode = (int) $entity->getRepliedNode()->get($entity->getFieldName())->status;

      // Current user is forum moderator?
      $is_moderator = $this->forumAccessManager->userIsForumModerator($record->getTermId(), $account);

      switch ($operation) {
        case 'view':
          $view_access = AccessResult::allowedIf($term->access($operation, $account))
            ->andIf(AccessResult::allowedIf($mode !== ForumReplyItemInterface::HIDDEN || $is_moderator))
            ->andIf(AccessResult::allowedIf($entity->isPublished() || $is_moderator)
              ->orIf(AccessResult::allowedIf($account->isAuthenticated() && $entity->getOwnerId() === $account->id() && $account->hasPermission('view own unpublished forum replies')))
            );

          $access = $access->isAllowed() ? $access->andIf($view_access) : $access->orIf($view_access);
          break;

        case 'update':
        case 'delete':
          $update_delete_access = AccessResult::allowedIf($entity->access('view', $account))
            ->andIf(AccessResult::allowedIf($mode === ForumReplyItemInterface::OPEN || $is_moderator))
            ->andIf(AccessResult::allowedIf($record->userHasPermission($account, $entity->getEntityTypeId(), $operation, $entity)));

          $access = $access->isAllowed() ? $access->andIf($update_delete_access) : $access->orIf($update_delete_access);
          break;
      }

      $access
        // Add forum reply to cache dependencies.
        ->addCacheableDependency($entity)
        // Add forum node to cache dependencies.
        ->addCacheableDependency($entity->getRepliedNode())
        // Add forum access record to cache dependencies.
        ->addCacheableDependency($record);
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(AccessResultInterface &$access, $entity_bundle = NULL, AccountInterface $account = NULL, array $context = []) {
    if (!empty($context['field_name']) && !empty($context['nid'])) {
      $field_name = $context['field_name'];

      /** @var \Drupal\node\NodeInterface $node */
      if (($node = $this->entityTypeManager->getStorage('node')->load($context['nid']))) {

        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $this->forumManager->getForumTermByNode($node);

        // Load forum access record.
        $record = $this->forumAccessManager->getForumAccessRecord($term ? $term->id() : 0);

        $create_access = AccessResult::allowedIf(
          ((int) $node->get($field_name)->status === ForumReplyItemInterface::OPEN || $this->forumAccessManager->userIsForumModerator($record->getTermId(), $account))
          && $record->userHasPermission($account, 'thunder_forum_reply', ForumAccessMatrixInterface::PERMISSION_CREATE)
        );

        $access = $access->isAllowed() ? $access->andIf($create_access) : $access->orIf($create_access);

        $access
          // Add forum node to cache dependencies.
          ->addCacheableDependency($node)
          // Add forum access record to cache dependencies.
          ->addCacheableDependency($record);
      }
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldAccess(AccessResultInterface &$access, $operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL) {
    if ($items) {
      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
      $reply = $items->getEntity();

      $term = $this->forumManager->getForumTermByNode($reply->getRepliedNode());

      // Add forum term to cache dependencies (if available).
      if ($term) {
        $access
          ->addCacheableDependency($term);
      }

      // Always hide path field, because URL paths have a given structure with no
      // need to change.
      if ($field_definition->getName() === 'path' && $operation === 'edit') {
        $access = $access->andIf(AccessResult::forbidden());
      }

      // Always grant access on all fields for forum administrators.
      elseif ($this->forumAccessManager->userIsForumAdmin($account) && !$access->isAllowed()) {
        $access = $access->orIf(AccessResult::allowed());
      }

      // Restrict fields for non-admin users (while respecting moderators).
      elseif (!$this->forumAccessManager->userIsForumAdmin($account) && $operation === 'edit') {
        $is_moderator = $this->forumAccessManager->userIsForumModerator($term ? $term->id() : 0, $account);

        // Build list of conditions for restricted fields.
        $restricted_fields = [
          'status' => $is_moderator,
          'langcode' => FALSE,
          'revision_log' => $is_moderator,
        ];

        if (isset($restricted_fields[$field_definition->getName()])) {
          $field_access = AccessResult::allowedIf(
            $restricted_fields[$field_definition->getName()]
          );

          $access = $access->isAllowed() ? $access->andIf($field_access) : $access->orIf($field_access);
        }
      }

      $access
        // Add forum node to cache dependencies.
        ->addCacheableDependency($reply->getRepliedNode())
        // Add forum reply to cache dependencies.
        ->addCacheableDependency($reply);
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions();
  }

}
