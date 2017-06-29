<?php

/**
 * @file
 * Provide views data for thunder_private_message.module.
 */

/**
 * Implements hook_views_data().
 */
function thunder_private_message_views_data() {
  $data = [];

  $data['message__tpm_title'] = [];

  $table_data = &$data['message__tpm_title'];
  $table_data['table']['group'] = t('Thunder private message');
  $table_data['table']['join']['message_field_data'] = [
    'field' => 'entity_id',
    'left_field' => 'mid',
  ];
  $table_data['tpm_title_value'] = [
    'title' => t('Subject'),
    'help' => t('Subject of private message.'),
    'field' => [
      'id' => 'thunder_private_message',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'standard',
    ],
  ];

  $table_data = &$data['message__tpm_message'];
  $table_data['table']['group'] = t('Thunder private message');
  $table_data['table']['join']['message_field_data'] = [
    'field' => 'entity_id',
    'left_field' => 'mid',
  ];
  $table_data['tpm_message_value'] = [
    'title' => t('Message content'),
    'help' => t('Contents of private message'),
    'field' => [
      'id' => 'markup',
      'format' => [
        'field' => 'tpm_message_format',
      ],
    ],
  ];

  $table_data = &$data['message__tpm_recipient'];
  $table_data['table']['group'] = t('Thunder private message');
  $table_data['table']['join']['message_field_data'] = [
    'field' => 'entity_id',
    'left_field' => 'mid',
  ];
  $table_data['tpm_recipient_target_id'] = [
    'title' => t('Message recipient'),
    'help' => t('Recipient of private message'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('Message recipient'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Message recipient'),
    ],
  ];

  $data['users_field_data']['thunder_private_message_send_link'] = [
    'real field' => 'uid',
    'title' => t('Send private message'),
    'help' => t('Create a link to send a private message to a user.'),
    'field' => [
      'id' => 'thunder_private_message_send_link',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function thunder_private_message_views_data_alter(array &$data) {
  /** @var \Drupal\flag\FlagInterface $flag */
  if (($flag = \Drupal::service('flag')->getFlagById('thunder_private_message_deleted'))) {

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_type_id = $flag->getFlaggableEntityTypeId();

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);

    if ($entity_type->hasHandlerClass('views_data')) {
      $base_table = $entity_type_manager->getHandler($entity_type_id, 'views_data')->getViewsTableForEntityType($entity_type);

      $data[$base_table]['thunder_private_message_flag_relationship'] = [
        'title' => t('Deleted private message flag'),
        'help' => t('Limit results to only those entities flagged as deleted private message (either by sender or recipient).'),
        'relationship' => [
          'group' => t('Flag'),
          'label' => t('Deleted private message flag'),
          'base' => 'flagging',
          'base field' => 'entity_id',
          'relationship field' => $entity_type->getKey('id'),
          'id' => 'thunder_private_message_deleted_flag_relationship',
          'flaggable' => $entity_type_id,
        ],
      ];
    }
  }

  // Messages.
  if (isset($data['message_field_data'])) {
    // Private message icon.
    $data['message_field_data']['thunder_private_message_icon'] = [
      'title' => t('Private message icon'),
      'help' => t('The private message icon.'),
      'field' => [
        'field' => 'mid',
        'id' => 'thunder_private_message_icon',
      ],
    ];
  }
}
