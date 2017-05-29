<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the forum reply schema handler.
 */
class ForumReplyStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['thunder_forum_reply_field_data']['indexes'] += [
      'thunder_forum_reply___status_pfrid' => [
        'pfrid',
        'status',
      ],
      'thunder_forum_reply___num_new' => [
        'nid',
        'status',
        'created',
        'frid',
      ],
      'thunder_forum_reply__entity_langcode' => [
        'nid',
        'default_langcode',
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'thunder_forum_reply_revision') {
      switch ($field_name) {
        case 'langcode':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
          
        case 'revision_uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;
      }
    }

    if ($table_name == 'thunder_forum_reply_field_data') {
      // Remove unneeded indexes.
      unset($schema['indexes']['thunder_forum_reply_field__pfrid__target_id']);

      switch ($field_name) {
        case 'status':
        case 'title':
          // Improves the performance of the indexes defined in
          // getEntitySchema().
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'changed':
        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'nid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'node_field_data', 'nid');
          break;

        case 'pfrid':
          $schema['fields'][$field_name]['not null'] = FALSE;
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'thunder_forum_reply_field_data', 'frid');
          break;

        case 'uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;
      }
    }

    return $schema;
  }

}
