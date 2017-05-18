<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides forum access helper interface.
 */
interface ForumAccessHelperInterface {

  /**
   * Alter forum comment form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterForumCommentForm(array &$form, FormStateInterface $form_state, $form_id);

  /**
   * Alter forum node form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterForumNodeForm(array &$form, FormStateInterface $form_state, $form_id);

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
   * Alter forum taxonomy term overview form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form ID.
   */
  public function alterForumTermOverviewForm(array &$form, FormStateInterface $form_state, $form_id);

}
