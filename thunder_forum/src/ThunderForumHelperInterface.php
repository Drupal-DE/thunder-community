<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides thunder forum helper interface.
 */
interface ThunderForumHelperInterface {

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

}
