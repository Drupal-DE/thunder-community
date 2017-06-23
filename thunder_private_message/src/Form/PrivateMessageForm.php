<?php

namespace Drupal\thunder_private_message\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\message_ui\Form\MessageForm as BaseMessageForm;

/**
 * Form controller for private message default form.
 *
 * @ingroup message_ui
 */
class PrivateMessageForm extends BaseMessageForm {

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    /** @var \Drupal\message\MessageInterface $message */
    $message = $this->entity;

    if (isset($actions['cancel'])) {
      if ($this->getRequest()->query->has('destination')) {
        $url = Url::fromUserInput($this->getRequest()->query->get('destination'));
      }

      else {
        $url = Url::fromRoute('entity.user.thunder_private_message.inbox', [
          'user' => $message->getOwnerId(),
        ]);
      }

      $link = Link::fromTextAndUrl($this->t('Cancel'), $url)->toString();

      $actions['cancel'] = [
        '#markup' => $link,
      ];
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Hide unnecessary 'text' element.
    if (isset($form['text'])) {
      $form['text']['#access'] = FALSE;
    }

    // Hide unnecessary 'advanced' element.
    if (isset($form['advanced'])) {
      $form['advanced']['#access'] = FALSE;
    }

    return $form;
  }

}
