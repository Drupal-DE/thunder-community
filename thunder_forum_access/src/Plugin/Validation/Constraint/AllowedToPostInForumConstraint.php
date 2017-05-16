<?php

namespace Drupal\thunder_forum_access\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "ThunderForumAccessAllowedToPostInForum",
 *   label = @Translation("Allowed to post in forum", context = "Validation"),
 * )
 */
class AllowedToPostInForumConstraint extends Constraint {

  public $selectForumMessage = 'Select a forum.';
  public $notAllowedMessage = 'You are not allowed to post in the %forum forum.';

}
