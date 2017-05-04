<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a hierarchical access control handler for forum terms.
 *
 * @ThunderAccessControlHandler(
 *   id = "forum_term_hierarchical",
 *   type = "taxonomy_term",
 *   weight = 5
 * )
 */
class ForumTermHierarchical extends ForumTermBase {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $parents = $this->storage->loadParents($entity->id());

    /* @var $term \Drupal\taxonomy\TermInterface */
    foreach ($parents as $term) {
      $result = $term->access($operation, $account, TRUE);
      if ($result->isForbidden()) {
        // If one of the parents is restricted, deny access.
        return $result;
      }
    }

    // Fallback to "I don't care".
    return AccessResult::neutral();
  }

}
