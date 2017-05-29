<?php

namespace Drupal\thunder_private_message\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\Message;
use Drupal\message_ui\Form\MessageForm;

/**
 * Form controller for the message_ui entity edit forms.
 *
 * @ingroup message_ui
 */
class MessageReplyForm extends MessageForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get message to reply to.
    /* @var $message \Drupal\message\MessageInterface */
    $message = $this->entity;

    // Create new private message.
    $defaults = [
      'template' => 'thunder_private_message',
      'tpm_recipient' => $message->getOwner(),
      'tpm_title' => $this->t('Re: @title', ['@title' => $message->tpm_title->first()->value]),
      'tpm_message' => '[quote]' . $message->tpm_message->first()->value . '[/quote]',
    ];
    $this->entity = Message::create($defaults);

    $form = parent::buildForm($form, $form_state);

    // Use different text for save-button.
    $form['actions']['save']['#value'] = $this->t('Reply');

    return $form;
  }

}
