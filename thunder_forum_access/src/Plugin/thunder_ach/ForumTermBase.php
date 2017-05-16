<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a basic access control handler for forum terms.
 *
 * @ThunderAccessControlHandler(
 *   id = "forum_term_base",
 *   type = "taxonomy_term",
 *   weight = 1
 * )
 */
class ForumTermBase extends ForumBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    return $this->forumManager->isForumTerm($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Load forum access record.
    $record = $this->forumAccessManager->getForumAccessRecord($entity->id());

    // Account is admin?
    $account_is_admin = $account->hasPermission('administer taxonomy') || $account->hasPermission('administer forums');

    switch ($operation) {
      case 'view':
        if ($record->userHasPermission($account, $entity->getEntityTypeId(), $operation)) {
          $result = AccessResult::allowed();
        }
        else {
          $result = AccessResult::forbidden();
        }
        break;

      case 'edit':
        // Only allow updates for admins or moderators.
        if ($account_is_admin || $this->forumAccessManager->userIsForumModerator($entity->id(), $account)) {
          $result = AccessResult::allowed();
        }
        else {
          $result = AccessResult::forbidden();
        }
        break;

      case 'delete':
        // Only allow deletion for admins.
        if ($account_is_admin) {
          $result = AccessResult::allowed();
        }
        else {
          $result = AccessResult::forbidden();
        }
        break;

      default:
        $result = parent::checkAccess($entity, $operation, $account);
    }

    $result
      // Take parent access result into account.
      ->orIf(parent::checkAccess($entity, $operation, $account))
      // Cache access result per user.
      ->cachePerUser()
      // Cache per permissions.
      ->cachePerPermissions()
      // Add forum access record to cache dependencies.
      ->addCacheableDependency($record);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($account->hasPermission('administer taxonomy') || $account->hasPermission('administer forums')) {
      return AccessResult::allowed();
    }

    // Forum moderators are allowed to edit title and description.
    $fields = [
      'name',
      'description',
    ];

    if (!empty($items) && $operation === 'edit') {
      if (in_array($field_definition->getName(), $fields) && $this->forumAccessManager->userIsForumModerator($items->getEntity()->id(), $account)) {
        return AccessResult::allowed();
      }

      return AccessResult::forbidden();
    }

    // @todo Restrict access to custom fields like 'parent' and 'weight'.
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
