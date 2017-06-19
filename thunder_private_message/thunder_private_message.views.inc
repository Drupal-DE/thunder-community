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
    'title' => t('Message title'),
    'help' => t('Title of private message'),
    'field' => [
      'id' => 'standard',
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

  $data['users_field_data']['pm_send'] = [
    'real field' => 'uid',
    'title' => t('Send private message'),
    'help' => t('Create a link to send a private message to a user.'),
    'field' => [
      'id' => 'tpm_send_message',
      'additional fields' => [
        'tpm_allow_messages' => 'tpm_allow_messages',
      ],
    ],
  ];

  return $data;
}
