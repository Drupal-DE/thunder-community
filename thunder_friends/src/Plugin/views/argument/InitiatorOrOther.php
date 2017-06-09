<?php

namespace Drupal\thunder_friends\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Plugin\views\argument\Uid;

/**
 * Argument handler to accept a user id.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("initiator_or_other")
 */
class InitiatorOrOther extends Uid {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['break_phrase']['#access'] = FALSE;
    $form['break_phrase']['#default_value'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $this->value = [$this->argument];

    $this->query->addField(NULL, "CASE WHEN $this->tableAlias.uid = $this->argument THEN $this->tableAlias.fuid ELSE $this->tableAlias.uid END", 'friend');

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : "OR ($this->tableAlias.uid IS NULL AND $this->tableAlias.fuid IS NULL)";

    $operator = empty($this->options['not']) ? '=' : '!=';
    $this->query->addWhereExpression(0, "$this->tableAlias.uid $operator $placeholder OR $this->tableAlias.fuid $operator $placeholder $null_check", [$placeholder => $this->argument]);
  }

}
