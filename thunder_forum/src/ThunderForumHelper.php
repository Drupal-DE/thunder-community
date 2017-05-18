<?php

namespace Drupal\thunder_forum;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides thunder forum helper service.
 */
class ThunderForumHelper implements ThunderForumHelperInterface {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a new ThunderForumHelper.
   *
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   */
  public function __construct(ThunderForumManagerInterface $forum_manager) {
    $this->forumManager = $forum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForumTermForm(array &$form, FormStateInterface $form_state, $form_id) {
    // Is forum taxonomy term form?
    if ($this->forumManager->isForumTermForm($form_id)) {
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
      if (array_key_exists($element['#value'], $this->forumManager->getChildren($vid, $tid))) {
        $form_state->setError($element, t('A forum must not be moved into one of its children.'));
      }
    }
  }

}
