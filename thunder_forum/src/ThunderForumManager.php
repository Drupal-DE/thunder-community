<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Form\FormStateInterface;
use Drupal\forum\ForumManager;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager service.
 */
class ThunderForumManager extends ForumManager implements ThunderForumManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function alterForumTermForm(array &$form, FormStateInterface $form_state, $form_id) {
    // Is forum taxonomy term form?
    if ($this->isForumTermForm($form_id)) {
      // 'Parent' field exists?
      if (isset($form['parent'][0])) {
        // Add element validation handler to disallow moving a forum into one of
        // its children.
        $form['parent'][0]['#element_validate'][] = [$this, 'elementValidateForumTermParent'];
      }
    }
  }

  /**
   * Form element validation handler for forum taxonomy term's 'parent' element.
   *
   * @see static::alterForumTermForm()
   */
  public function elementValidateForumTermParent($element, FormStateInterface $form_state) {
    $tid = $form_state->getValue('tid');
    $vid = $form_state->getValue('vid');

    if (isset($tid) && !empty($vid)) {
      // Value is one of the forum taxonomy term's children?
      if (array_key_exists($element['#value'], $this->getChildren($vid, $tid))) {
        $form_state->setError($element, t('A forum must not be moved into one of its children.'));
      }
    }
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
      'taxonomy_term_forums_container_form'
    ]);
  }

}
