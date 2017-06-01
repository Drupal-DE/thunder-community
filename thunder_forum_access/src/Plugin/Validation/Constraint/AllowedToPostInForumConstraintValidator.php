<?php

namespace Drupal\thunder_forum_access\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\thunder_forum_access\Access\ForumAccessManagerInterface;
use Drupal\thunder_forum_access\Access\ForumAccessMatrixInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the AllowedToPostInForum constraint.
 */
class AllowedToPostInForumConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The forum access manager.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessManagerInterface
   */
  protected $forumAccessManager;

  /**
   * Creates a new AllowedToPostInForumConstraintValidator instance.
   *
   * @param \Drupal\thunder_forum_access\Access\ForumAccessManagerInterface $forum_access_manager
   *   The forum access manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ForumAccessManagerInterface $forum_access_manager, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->forumAccessManager = $forum_access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('thunder_forum_access.forum_access_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $item = $items->first();

    if (!isset($item)) {
      return NULL;
    }

    // Verify that a term has been selected.
    if (!$item->entity) {
      $this->context->addViolation($constraint->selectForumMessage);
    }

    // Load access record.
    $record = $this->forumAccessManager->getForumAccessRecord($item->entity->id());

    // Must be allowed to post in forum.
    if (!$record->userHasPermission($this->currentUser, $items->getEntity()->getEntityTypeId(), ForumAccessMatrixInterface::PERMISSION_CREATE)) {
      $this->context->addViolation($constraint->notAllowedMessage, ['%forum' => $item->entity->getName()]);
    }
  }

}
