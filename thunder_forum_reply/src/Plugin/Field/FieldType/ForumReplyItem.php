<?php

namespace Drupal\thunder_forum_reply\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'thunder_forum_reply' field type.
 *
 * @FieldType(
 *   id = "thunder_forum_reply",
 *   label = @Translation("Forum replies"),
 *   description = @Translation("This field manages configuration and presentation of forum replies on a forum topic node."),
 *   list_class = "\Drupal\thunder_forum_reply\ForumReplyFieldItemList",
 *   default_widget = "thunder_forum_reply_default",
 *   default_formatter = "thunder_forum_reply_default"
 * )
 */
class ForumReplyItem extends FieldItemBase implements ForumReplyItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'per_page' => 50,
      'form_location' => ForumReplyItemInterface::FORM_BELOW,
      'preview' => DRUPAL_OPTIONAL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Forum reply behavior status'))
      ->setRequired(TRUE);

    $properties['frid'] = DataDefinition::create('integer')
      ->setLabel(t('Last forum reply ID'));

    $properties['last_reply_timestamp'] = DataDefinition::create('integer')
      ->setLabel(t('Last forum reply timestamp'))
      ->setDescription(t('The time that the last forum reply was created.'));

    $properties['last_reply_uid'] = DataDefinition::create('integer')
      ->setLabel(t('Last forum reply user ID'));

    $properties['reply_count'] = DataDefinition::create('integer')
      ->setLabel(t('Number of forum replies'))
      ->setDescription(t('The number of forum replies.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'status' => array(
          'description' => 'Whether forum replies are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'default' => 0,
        ),
      ),
      'indexes' => array(),
      'foreign keys' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $settings = $this->getSettings();

    // Replies per page.
    $element['per_page'] = array(
      '#type' => 'number',
      '#title' => t('Forum replies per page'),
      '#default_value' => $settings['per_page'],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
    );

    // Form location.
    $element['form_location'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show forum reply form on the same page as other forum replies'),
      '#default_value' => $settings['form_location'],
    );

    // Preview behavior.
    $element['preview'] = array(
      '#type' => 'radios',
      '#title' => t('Preview forum reply'),
      '#default_value' => $settings['preview'],
      '#options' => array(
        DRUPAL_DISABLED => t('Disabled'),
        DRUPAL_OPTIONAL => t('Optional'),
        DRUPAL_REQUIRED => t('Required'),
      ),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $statuses = [
      ForumReplyItemInterface::HIDDEN,
      ForumReplyItemInterface::CLOSED,
      ForumReplyItemInterface::OPEN,
    ];
    return [
      'status' => $statuses[mt_rand(0, count($statuses) - 1)],
    ];
  }

}
