<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Session\AccountInterface;
use Drupal\forum\ForumManager;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager service.
 */
class ThunderForumManager extends ForumManager implements ThunderForumManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function getForumStatistics($tid) {
    // @todo override ThunderForumManager::getForumStatistics().
    return parent::getForumStatistics($tid);
  }

  /**
   * {@inheritdoc}
   */
  public function getForumTermByNode(NodeInterface $node) {
    $field_name = 'taxonomy_forums';

    // Is forum node type and has forum taxonomy term?
    if ($this->checkNodeType($node) && !$node->get($field_name)->isEmpty()) {
      return $node->get($field_name)->first()->entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPost($tid) {
    // @todo override ThunderForumManager::getLastPost().
    return parent::getLastPost($tid);
  }

  /**
   * {@inheritdoc}
   */
  public function getParent($tid) {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityManager
      ->getStorage('taxonomy_term');

    if (($parents = $term_storage->loadParents($tid))) {
      return reset($parents);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId($tid) {
    $parent = $this->getParent($tid);

    return $parent ? $parent->id() : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopics($tid, AccountInterface $account) {
    // @todo override ThunderForumManager::getTopics().
    return parent::getTopics($tid, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getTopicOrder($sortby) {
    // @todo override ThunderForumManager::getTopicOrder().
    return parent::getTopicOrder($sortby);
  }

  /**
   * {@inheritdoc}
   */
  public function isForumContainer(TermInterface $term) {
    return $this->isForumTerm($term) && $term->hasField('forum_container') && !empty($term->forum_container->value);
  }

  /**
   * {@inheritdoc}
   */
  public function isForumTerm(TermInterface $term) {
    return $term->bundle() === $this->configFactory->get('forum.settings')->get('vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function isForumTermForm($form_id) {
    return in_array($form_id, [
      'taxonomy_term_forums_forum_form',
      'taxonomy_term_forums_container_form',
    ]);
  }

}
