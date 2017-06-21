<?php

namespace Drupal\thunder_private_message\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "thunder_private_message_user",
 *   label = @Translation("Private message: user selection"),
 *   entity_types = {"user"},
 *   group = "thunder_community",
 *   weight = 2
 * )
 */
class PrivateMessageUserSelection extends UserSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $config = \Drupal::config('thunder_private_message.settings');

    // Override parent handler settings and set configuration.
    $handler_settings = &$this->configuration['handler_settings'];
    $handler_settings['include_anonymous'] = FALSE;

    // Set roles from config.
    $roles = $config->get('recipients.roles') ?: [];
    if (!empty($roles)) {
      $handler_settings['filter']['role'] = array_keys(array_filter($roles));
    }

    $query = parent::buildEntityQuery($match, $match_operator);

    $account = \Drupal::currentUser();
    if ($account->hasPermission('bypass thunder_private_message access') || $account->hasPermission('administer thunder_private_message')) {
      return $query;
    }
    // Exclude users not wanting private messages.
    $query->condition('tpm_allow_messages', 1, '=');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // Override parent handler settings and set configuration.
    $handler_settings = &$this->configuration['handler_settings'];
    $handler_settings['include_anonymous'] = FALSE;

    return parent::entityQueryAlter($query);
  }

}
