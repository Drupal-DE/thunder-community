<?php

namespace Drupal\thunder_forum_access\Plugin\pach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access control handler for forum terms.
 *
 * @AccessControlHandler(
 *   id = "thunder_forum_access_term",
 *   type = "taxonomy_term",
 *   weight = 1
 * )
 */
class ForumTerm extends ForumBase {

  /**
   * {@inheritdoc}
   */
  public function access(AccessResultInterface &$access, EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    // Load forum access record.
    $record = $this->forumAccessManager->getForumAccessRecord($entity->id());

    switch ($operation) {
      case 'view':
        $view_access = AccessResult::allowedIf(
          $record->userHasPermission($account, $entity->getEntityTypeId(), $operation)
        );

        $access = $access->isAllowed() ? $access->andIf($view_access) : $access->orIf($view_access);
        break;

      case 'update':
        // Only allow updates for admins or moderators.
        $update_access = AccessResult::allowedIf(
          $this->forumAccessManager->userIsForumModerator($entity->id(), $account)
          && $entity->access('view', $account)
        );

        $access = $access->isAllowed() ? $access->andIf($update_access) : $access->orIf($update_access);
        break;

      case 'delete':
        // Only allow deletion for admins.
        $delete_access = AccessResult::allowedIf(
          $this->forumAccessManager->userIsForumAdmin($account)
          && $entity->access('view', $account)
        );

        $access = $access->isAllowed() ? $access->andIf($delete_access) : $access->orIf($delete_access);
        break;
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions()
      // Add forum access record to cache dependencies.
      ->addCacheableDependency($record);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    return $this->forumManager->isForumTerm($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Access for all other non-field API fields is checked via the corresponding
   * term form alter method in the forum access helper service.
   *
   * @see \Drupal\thunder_forum_access\Access\ForumAccessHelperInterface::alterForumTermForm()
   */
  public function fieldAccess(AccessResultInterface &$access, $operation, FieldDefinitionInterface $field_definition, AccountInterface $account = NULL, FieldItemListInterface $items = NULL) {
    if ($items) {
      // Load forum access record.
      $record = $this->forumAccessManager->getForumAccessRecord($items->getEntity()->isNew() ? 0 : $items->getEntity()->id());

      // Always hide status field, because this access plugin makes it useless.
      if ($field_definition->getName() === 'status') {
        $access = $access->andIf(AccessResult::forbidden());
      }

      // Always hide path field, because URL paths have a given structure with no
      // need to change.
      elseif ($field_definition->getName() === 'path' && $operation === 'edit') {
        $access = $access->andIf(AccessResult::forbidden());
      }

      // Always grant access on all fields for forum administrators.
      elseif ($this->forumAccessManager->userIsForumAdmin($account) && !$access->isAllowed()) {
        $access = $access->orIf(AccessResult::allowed());
      }

      // Restrict fields for non-admin users (while respecting moderators).
      elseif (!$this->forumAccessManager->userIsForumAdmin($account) && $operation === 'edit') {
        $is_moderator = $this->forumAccessManager->userIsForumModerator($items->getEntity() && $items->getEntity()->id() ? $items->getEntity()->id() : 0, $account);

        // Build list of conditions for restricted fields.
        $restricted_fields = [
          'description' => $is_moderator,
          'name' => $is_moderator,
          'langcode' => FALSE,
        ];

        if (isset($restricted_fields[$field_definition->getName()])) {
          $field_access = AccessResult::allowedIf(
            $restricted_fields[$field_definition->getName()]
          );

          $access = $access->isAllowed() ? $access->andIf($field_access) : $access->orIf($field_access);
        }
      }

      $access
        // Add forum access record to cache dependencies.
        ->addCacheableDependency($record);
    }

    $access
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions();

  }

}
