<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_ach\Plugin\ThunderAccessControlHandlerBase;

/**
 * Provides a basic access control handler for forum terms.
 *
 * @ThunderAccessControlHandler(
 *   id = "forum_node_base",
 *   type = "node",
 *   weight = 1
 * )
 */
class ForumNodeBase extends ThunderAccessControlHandlerBase {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->forumManager = \Drupal::service('forum_manager');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    /* @var $entity \Drupal\node\NodeInterface */
    return $entity->hasField('taxonomy_forums');
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->taxonomy_forums->isEmpty()) {
      return parent::checkAccess($entity, $operation, $account);
    }
    /* @var $forums \Drupal\Core\Entity\EntityInterface[] */
    $forums = $entity->taxonomy_forums->referencedEntities();
    switch ($operation) {
      case 'view':
        return $forums[0]->access($operation, $account, TRUE);
    }

    // Fallback to default.
    return parent::checkAccess($entity, $operation, $account);
  }

}
