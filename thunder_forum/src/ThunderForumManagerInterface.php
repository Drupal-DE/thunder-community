<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Form\FormStateInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Provides thunder forum manager interface.
 */
interface ThunderForumManagerInterface extends ForumManagerInterface {

  /**
   * Alter forum taxonomy term form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterForumTermForm(array &$form, FormStateInterface $form_state, $form_id);

  /**
   * Utility method to fetch the direct ancestor forum for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent for.
   *
   * @return \Drupal\taxonomy\TermInterface[]|null
   *   The parent forum taxonomy term on success, otherwise NULL.
   */
  public function getParent($tid);

  /**
   * Utility method to fetch the direct ancestor forum ID for a given forum.
   *
   * @param int $tid
   *   The forum ID to fetch the parent ID for.
   *
   * @return int
   *   The parent forum taxonomy term ID on success, otherwise '0'.
   */
  public function getParentId($tid);

  /**
   * Returns TRUE if the given taxonomy term is a forum container.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum container.
   */
  public function isForumContainer(TermInterface $term);

  /**
   * Returns TRUE if the given taxonomy term is a forum term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum term.
   */
  public function isForumTerm(TermInterface $term);

  /**
   * Returns TRUE if the given form ID is for a forum taxonomy term form.
   *
   * @param string $form_id
   *   A form ID.
   *
   * @return bool
   *   Boolean indicating whether the given form ID is for a forum taxonomy term
   *   form.
   */
  public function isForumTermForm($form_id);

}
