<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface;

/**
 * Provides a basic access control handler for forum terms.
 *
 * @ThunderAccessControlHandler(
 *   id = "forum_node_base",
 *   type = "node",
 *   weight = 1
 * )
 */
class ForumNodeBase extends ForumBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    return $this->forumManager->checkNodeType($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // No forum reference available?
    if ($entity->get('taxonomy_forums')->isEmpty()) {
      return parent::checkAccess($entity, $operation, $account)
        // Cache access result per user.
        ->cachePerUser()
        // Add entity to cache dependencies.
        ->addCacheableDependency($entity);
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $entity->get('taxonomy_forums')->first()->entity;

    // Load forum access record.
    $record = $this->forumAccessManager->getForumAccessRecord($term->id());

    switch ($operation) {
      case 'view':
        $result = $term->access($operation, $account, TRUE);
        break;

      case 'update':
      case 'delete':
        if ($record->userHasPermission($account, $entity->getEntityTypeId(), $operation, $entity)) {
          $result = AccessResult::allowed()
            ->orIf($this->checkAccess($entity, 'view', $account));
        }
        else {
          $result = AccessResult::forbidden();
        }
        break;

      default:
        $result = parent::checkAccess($entity, $operation, $account);
        break;
    }

    $result
      // Take parent access result into account.
      ->orIf(parent::checkAccess($entity, $operation, $account))
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions()
      // Add forum access record to cache dependencies.
      ->addCacheableDependency($record)
      // Add entity to cache dependencies.
      ->addCacheableDependency($entity);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
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
    if ($record->userHasPermission($account, 'node', ForumAccessMatrixInterface::PERMISSION_CREATE)) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::forbidden();
    }

    return $result
      // Cache access result per user.
      ->cachePerUser()
      // Cache access result per URL.
      ->addCacheContexts($cache_contexts)
      // Add forum access record to cache dependencies.
      ->addCacheableDependency($record);
  }

}
