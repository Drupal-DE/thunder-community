<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Session\AccountInterface;
use Drupal\forum\ForumManager;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager service.
 */
class ThunderForumManager extends ForumManager implements ThunderForumManagerInterface {

  /**
   * Array of forum members keyed by forum (term) id.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $forumMembers = [];

  /**
   * Array of forum moderators keyed by forum (term) id.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $forumModerators = [];

  /**
   * {@inheritdoc}
   */
  public function getMembers(TermInterface $term) {
    $tid = $term->id();
    if (!empty($this->forumMembers[$tid])) {
      return $this->forumMembers[$tid];
    }

    $this->forumMembers[$tid] = [];
    if ($term->field_forum_members->isEmpty()) {
      return $this->forumMembers[$tid];
    }
    /* @var $member \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem */
    foreach ($term->field_forum_members as $member) {
      /* @var $account \Drupal\user\UserInterface */
      if (($account = $member->entity) === NULL) {
        continue;
      }
      $this->forumMembers[$tid][$account->id()] = $account;
    }
    return $this->forumMembers[$tid];
  }

  /**
   * {@inheritdoc}
   */
  public function getModerators(TermInterface $term) {
    $tid = $term->id();
    if (!empty($this->forumModerators[$tid])) {
      return $this->forumModerators[$tid];
    }

    $this->forumModerators[$tid] = [];
    if ($term->field_forum_moderators->isEmpty()) {
      return $this->forumModerators[$tid];
    }
    /* @var $moderator \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem */
    foreach ($term->field_forum_moderators as $moderator) {
      /* @var $account \Drupal\user\UserInterface */
      if (($account = $moderator->entity) === NULL) {
        continue;
      }
      $this->forumModerators[$tid][$account->id()] = $account;
    }
    return $this->forumModerators[$tid];
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
  public function isPrivate(TermInterface $term) {
    return $term->__isset('field_forum_is_private') ? (bool) $term->field_forum_is_private->value : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked(TermInterface $term) {
    return $term->__isset('field_forum_is_locked') ? (bool) $term->field_forum_is_locked->value : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isModerator(TermInterface $term, AccountInterface $account) {
    $moderators = $this->getModerators($term);
    return isset($moderators[$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function isMember(TermInterface $term, AccountInterface $account) {
    $members = $this->getMembers($term);
    return isset($members[$account->id()]);
  }

}
