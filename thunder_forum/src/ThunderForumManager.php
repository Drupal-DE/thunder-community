<?php

namespace Drupal\thunder_forum;

use Drupal\forum\ForumManager;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager service.
 */
class ThunderForumManager extends ForumManager implements ThunderForumManagerInterface {

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
  public function isForumTerm(TermInterface $term) {
    return $term->bundle() === $this->configFactory->get('forum.settings')->get('vocabulary');
  }

}
