<?php

namespace Drupal\thunder_private_message\Plugin\views\field;

use Drupal\Core\Url as CoreUrl;
use Drupal\views\Plugin\views\field\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to provide a simple link to write a private message.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("tpm_send_message")
 */
class SendMessage extends Url {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $account = \Drupal::currentUser();
    $uid = $this->getValue($values);
    if (empty($values->users_field_data_tpl_allow_messages) && !($account->hasPermission('bypass thunder_private_message access') || $account->hasPermission('administer thunder_private_message'))) {
      // The user does not want to receive messages and the current user is not
      // allowed to ignore this.
      return;
    }
    // Build link.
    $value = sprintf('user/%d/pm/add/%d', $account->id(), $uid);
    if (!empty($this->options['display_as_link'])) {
      return \Drupal::l($this->t('Send private message'), CoreUrl::fromUserInput('/' . $value));
    }
    else {
      return $this->sanitizeValue($value, 'url');
    }
  }

}
