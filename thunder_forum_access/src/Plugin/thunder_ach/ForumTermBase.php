<?php

namespace Drupal\thunder_forum_access\Plugin\thunder_ach;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_ach\Plugin\ThunderAccessControlHandlerBase;

/**
 * Provides a basic access control handler for forum terms.
 *
 * @ThunderAccessControlHandler(
 *   id = "forum_term_base",
 *   type = "taxonomy_term",
 *   weight = 1
 * )
 */
class ForumTermBase extends ThunderAccessControlHandlerBase {

  /**
   * The vocabulary ID of forum terms.
   *
   * @todo Make configurable.
   *
   * @var string
   */
  protected $vocabularyId = 'forums';

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
    /* @var $entity \Drupal\taxonomy\TermInterface */
    return $this->vocabularyId === $entity->getVocabularyId();
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $private = $this->forumManager->isPrivate($entity);
    $is_member = $this->forumManager->isMember($entity, $account);
    $is_moderator = $this->forumManager->isModerator($entity, $account);
    switch ($operation) {
      case 'view':
        return AccessResult::forbiddenIf($private && !($is_moderator || $is_member));

      case 'update':
        return AccessResult::allowedIf($is_moderator);
    }
    // Fallback.
    return parent::checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed();
    }
    // Forum moderators are allowed to edit title and description.
    $fields = ['name', 'description'];
    if (!empty($items) && 'edit' === $operation && in_array($field_definition->getName(), $fields)) {
      $is_moderator = $this->forumManager->isModerator($items->getEntity(), $account);
      return AccessResult::forbiddenIf(!$is_moderator);
    }
    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

}
