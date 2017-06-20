<?php

namespace Drupal\thunder_forum_reply_history\Plugin\views\filter;

use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Filter for new forum replies.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("thunder_forum_reply_history_timestamp")
 */
class ForumReplyHistoryTimestamp extends FilterPluginBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->no_operator = TRUE;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    unset($form['expose']['required']);
    unset($form['expose']['multiple']);
    unset($form['expose']['remember']);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // Only present a checkbox for the exposed filter itself. There's no way
    // to tell the difference between not checked and the default value, so
    // specifying the default value via the views UI is meaningless.
    if ($form_state->get('exposed')) {
      if (isset($this->options['expose']['label'])) {
        $label = $this->options['expose']['label'];
      }
      else {
        $label = $this->t('Has new content');
      }
      $form['value'] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $this->value,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // This can only work if we're authenticated in.
    if (!\Drupal::currentUser()->isAuthenticated()) {
      return;
    }

    // Don't filter if we're exposed and the checkbox isn't selected.
    if ((!empty($this->options['exposed'])) && empty($this->value)) {
      return;
    }

    $limit = REQUEST_TIME - HISTORY_READ_LIMIT;

    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $reply_table = $this->query->ensureTable('thunder_forum_reply_field_data', $this->relationship);

    // NULL means a history record doesn't exist. That's clearly new content.
    // Unless it's very very old content. Everything in the query is already
    // type safe cause none of it is coming from outside here.
    $this->query->addWhereExpression($this->options['group'], "($field IS NULL AND ($reply_table.created > (***CURRENT_TIME*** - $limit))) OR $field < $reply_table.created");
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
  }

}
