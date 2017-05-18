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
 *   group = "default",
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

    return parent::buildEntityQuery($match, $match_operator);
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
