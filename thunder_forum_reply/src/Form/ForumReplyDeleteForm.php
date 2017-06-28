<?php

namespace Drupal\thunder_forum_reply\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting a forum reply.
 */
class ForumReplyDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */
    $entity = $this->entity;

    return $entity->getRepliedNode()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Any related responses to this forum reply will be lost. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The forum reply and all its related responses have been deleted.');
  }

  /**
   * {@inheritdoc}
   */
  public function logDeletionMessage() {
    $this->logger('thunder_forum_reply')->notice('Deleted forum reply @frid and its related responses.', [
      '@frid' => $this->entity->id(),
    ]);
  }

}
