<?php

namespace Drupal\thunder_forum_access\Plugin\views\filter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_views_taxonomy\Plugin\views\filter\SearchApiTerm;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a dynamic filter searching through forum taxonomy terms.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("thunder_forum_access_search_api_forum_access")
 */
class SearchApiForumAccess extends SearchApiTerm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $vocabulary_storage, $term_storage);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {
    // Forum term field on indexed nodes.
    $options_node = [];
    foreach ($this->getIndex()->getFieldsByDatasource('entity:node') as $key => $field) {
      $options_node[$key] = $field->getLabel();
    }
    $form['field_node'] = [
      '#type' => 'select',
      '#title' => $this->t('Forum term field on indexed nodes'),
      '#default_value' => !empty($this->options['field_node']) ? $this->options['field_node'] : '',
      '#required' => TRUE,
      '#options' => $options_node,
    ];

    // Forum term field on indexed forum replies.
    $options_thunder_forum_reply = [];
    foreach ($this->getIndex()->getFieldsByDatasource('entity:thunder_forum_reply') as $key => $field) {
      $options_thunder_forum_reply[$key] = $field->getLabel();
    }
    $form['field_thunder_forum_reply'] = [
      '#type' => 'select',
      '#title' => $this->t('Forum term field on indexed forum replies'),
      '#default_value' => !empty($this->options['field_thunder_forum_reply']) ? $this->options['field_thunder_forum_reply'] : '',
      '#required' => TRUE,
      '#options' => $options_thunder_forum_reply,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('taxonomy_vocabulary'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['field_node'] = ['default' => 'forums'];
    $options['field_thunder_forum_reply'] = ['default' => 'reply_forums'];

    return $options;
  }

  /**
   * Get a list of forum terms the current user has access to.
   *
   * @see thunder_forum_access_query_taxonomy_term_access_alter()
   *
   * @return array
   *   List of term IDs the current user has access to.
   */
  protected function getAccessibleTerms() {
    $result =& drupal_static(get_class($this) . '::' . __METHOD__, NULL);

    if (!isset($result)) {
      /* @var $query \Drupal\Core\Entity\Query\QueryInterface */
      $query = $this->termStorage->getQuery()
        ->condition('vid', 'forums')
        ->addTag('taxonomy_term_access')
        ->addTag('thunder_forum_access');

      $result = $query->execute();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Fetch accessible terms.
    $this->value = $this->getAccessibleTerms();
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    // No operator should be selectable.
  }

  /**
   * {@inheritdoc}
   */
  protected function opHelper() {
    if (empty($this->value)) {
      return;
    }

    if ($this->currentUser->hasPermission('administer forums')) {
      return;
    }

    // @todo Should this throw an exception when required config is missing?
    if (empty($this->options['field_node']) || empty($this->options['field_thunder_forum_reply'])) {
      return;
    }

    $field_node = $this->options['field_node'];
    $field_thunder_forum_reply = $this->options['field_thunder_forum_reply'];

    // ORed multiple values.
    if ($this->operator !== 'and') {
      $condition_group = $this->getQuery()->createConditionGroup('OR');
      $operator = $this->operator === 'not' ? 'NOT IN' : 'IN';

      $condition_group->addCondition($field_node, $this->value, $operator);
      $condition_group->addCondition($field_thunder_forum_reply, $this->value, $operator);

      $this->getQuery()->addConditionGroup($condition_group, $this->options['group']);

      return;
    }

    // ANDed multiple values.
    $condition_group = $this->getQuery()->createConditionGroup();
    foreach ($this->value as $value) {
      $sub_condition_group = $this->getQuery()->createConditionGroup('OR');
      $sub_condition_group->addCondition($field_node, $value, '=');
      $sub_condition_group->addCondition($field_thunder_forum_reply, $value, '=');
      $condition_group->addConditionGroup($sub_condition_group, $this->options['group']);
    }

    $this->getQuery()->addConditionGroup($condition_group, $this->options['group']);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->valueOptions = NULL;
    parent::validate();
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    // No value should be selectable.
  }

}
