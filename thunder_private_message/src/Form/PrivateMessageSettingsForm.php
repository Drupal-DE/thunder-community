<?php

namespace Drupal\thunder_private_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Configure private message settings.
 */
class PrivateMessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_private_message_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['thunder_private_message.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('thunder_private_message.settings');
    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));

    $form['recipients'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Recipients'),
    ];

    $form['recipients']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#description' => $this->t('Limit the list of available recipients to selected roles. If none are selected, all are allowed.'),
      '#default_value' => $config->get('recipients.roles') ?: [],
      '#options' => $roles,
    ];

    // Special handling for the inevitable "Authenticated user" role.
    $form['recipients']['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('thunder_private_message.settings');

    $config->set('recipients.roles', array_filter($form_state->getValue('roles')));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
