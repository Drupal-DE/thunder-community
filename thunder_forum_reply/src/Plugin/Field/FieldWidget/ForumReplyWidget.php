<?php

namespace Drupal\thunder_forum_reply\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;

/**
 * Provides a default forum reply widget.
 *
 * @FieldWidget(
 *   id = "thunder_forum_reply_default",
 *   label = @Translation("Forum replies"),
 *   field_types = {
 *     "thunder_forum_reply"
 *   }
 * )
 */
class ForumReplyWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    // Status.
    $element['status'] = [
      '#type' => 'radios',
      '#title' => t('Forum replies'),
      '#title_display' => 'invisible',
      '#default_value' => $items->status,
      '#options' => [
        ForumReplyItemInterface::OPEN => t('Open'),
        ForumReplyItemInterface::CLOSED => t('Closed'),
        ForumReplyItemInterface::HIDDEN => t('Hidden'),
      ],
      ForumReplyItemInterface::OPEN => [
        '#description' => t('Users with the "Create forum replies" permission can post forum replies.'),
      ],
      ForumReplyItemInterface::CLOSED => [
        '#description' => t('Users cannot post forum replies, but existing forum replies will be displayed.'),
      ],
      ForumReplyItemInterface::HIDDEN => [
        '#description' => t('Forum replies are hidden from view.'),
      ],
    ];

    // If the entity doesn't have any forum replies, the "hidden" option makes
    // no sense, so don't even bother presenting it to the user unless this is
    // the default value widget on the field settings form.
    if (!$this->isDefaultValueWidget($form_state) && !$items->reply_count) {
      $element['status'][ForumReplyItemInterface::HIDDEN]['#access'] = FALSE;
      // Also adjust the description of the "closed" option.
      $element['status'][ForumReplyItemInterface::CLOSED]['#description'] = t('Users cannot post forum replies.');
    }

    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      // Get default value from the field.
      $field_default_values = $this->fieldDefinition->getDefaultValue($entity);

      // Override widget title to be helpful for end users.
      $element['#title'] = $this->t('Forum reply settings');

      $element += [
        '#type' => 'details',
        // Open the details when the selected value is different to the stored
        // default values for the field.
        '#open' => ($items->status != $field_default_values[0]['status']),
        '#group' => 'advanced',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Add default values for statistics properties because we don't want to
    // have them in form.
    foreach ($values as &$value) {
      $value += [
        'frid' => 0,
        'last_reply_timestamp' => 0,
        'last_reply_uid' => 0,
        'reply_count' => 0,
      ];
    }

    return $values;
  }

}
