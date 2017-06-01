<?php

namespace Drupal\thunder_forum_access\Plugin\pach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface;

/**
 * Provides an access control handler for forum nodes.
 *
 * @AccessControlHandler(
 *   id = "thunder_forum_access_node",
 *   type = "node",
 *   weight = 1
 * )
 */
class ForumNode extends ForumBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // No forum reference available.
    if (!($term = $this->forumManager->getForumTermByNode($entity))) {
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

      switch ($operation) {
        case 'view':
          $view_access = AccessResult::allowedIf(
            $term->access($operation, $account)
          );

          $access = $access->isAllowed() ? $access->andIf($view_access) : $access->orIf($view_access);
          break;

        case 'update':
        case 'delete':
          $update_delete_access = AccessResult::allowedIf(
            $record->userHasPermission($account, $entity->getEntityTypeId(), $operation, $entity)
            && $entity->access('view', $account)
          );

          $access = $access->isAllowed() ? $access->andIf($update_delete_access) : $access->orIf($update_delete_access);
          break;
      }

      $access
        // Add forum access record to cache dependencies.
        ->addCacheableDependency($record);
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions()
      // Add entity to cache dependencies.
      ->addCacheableDependency($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    return $this->forumManager->checkNodeType($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess(AccessResultInterface &$access, $entity_bundle = NULL, AccountInterface $account = NULL, array $context = []) {
    $tid = 0;
    $cache_contexts = ['url'];

    // Forum term page.
    if (($term = $this->routeMatch->getParameter('taxonomy_term')) && $this->forumManager->isForumTerm($term)) {
      $tid = $term->id();
    }

    // Node create form (with 'forum_id' URL parameter).
    elseif ($this->requestStack->getCurrentRequest()->query->has('forum_id')) {
      $tid = $this->requestStack->getCurrentRequest()->get('forum_id');
      $cache_contexts[] = 'url.query_args:forum_id';
    }

    // Load forum access record.
    $record = $this->forumAccessManager->getForumAccessRecord($tid);

    // User is allowed to create forum nodes?
    $create_access = AccessResult::allowedIf(
      $record->userHasPermission($account, 'node', ForumAccessMatrixInterface::PERMISSION_CREATE)
    );

    $access = $access->isAllowed() ? $access->andIf($create_access) : $access->orIf($create_access);

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache access result per URL.
      ->addCacheContexts($cache_contexts)
      // Add forum access record to cache dependencies.
      ->addCacheableDependency($record);
  }

  /**
   * {@inheritdoc}
   *
   * Access for all other non-field API fields is checked via the corresponding
   * node form alter method in the forum access helper service.
   *
   * @see \Drupal\thunder_forum_access\Access\ForumAccessHelperInterface::alterForumNodeForm()
   */
  public function fieldAccess(AccessResultInterface &$access, $operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL) {
    if ($items) {
      $term = $this->forumManager->getForumTermByNode($items->getEntity());

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
          'created' => FALSE,
          'forum_replies' => $is_moderator,
          'langcode' => FALSE,
          'promote' => $is_moderator,
          'publish_on' => FALSE,
          'sticky' => $is_moderator,
          'taxonomy_forums' => $items->getEntity()->isNew() || $is_moderator,
          'uid' => FALSE,
          'unpublish_on' => FALSE,
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
        ->addCacheableDependency($items->getEntity());
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions();
  }

}
