<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * A custom field storage definition class.
 *
 * For convenience we extend from BaseFieldDefinition although this should not
 * implement FieldDefinitionInterface.
 */
class FieldDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}
