<?php

namespace Drupal\thunder_private_message\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Plugin\views\relationship\FlagViewsRelationship;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views relationship to select private messages flagged as deleted.
 *
 * @ViewsRelationship("thunder_private_message_deleted_flag_relationship")
 */
class DeletedPrivateMessageFlagViewsRelationship extends FlagViewsRelationship {

  /**
   * The views plugin join manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * Constructs a FlagViewsRelationship object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The kill switch.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KillSwitch $page_cache_kill_switch, FlagServiceInterface $flag_service, ViewsHandlerManager $join_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $page_cache_kill_switch, $flag_service);

    $this->joinManager = $join_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('page_cache_kill_switch'),
      $container->get('flag'),
      $container->get('plugin.manager.views.join')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['flag']['default'] = 'thunder_private_message_deleted';
    $options['user_scope']['default'] = 'thunder_private_message_sender';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Disable flag ID changes.
    $form['flag']['#disabled'] = TRUE;

    // Add sender/recipient user scope options.
    $form['user_scope']['#options']['thunder_private_message_sender'] = $this->t('Private message sender');
    $form['user_scope']['#options']['thunder_private_message_recipient'] = $this->t('Private message recipient');

    // Remove obsolete user scope options.
    unset($form['user_scope']['#options']['current']);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!($flag = $this->getFlag())) {
      return;
    }

    // User scope: Private message sender.
    if ($this->options['user_scope'] == 'thunder_private_message_sender' && !$flag->isGlobal()) {
      $this->ensureMyTable();

      $this->definition['extra'][] = [
        'field' => 'uid',
        'left_field' => 'uid',
      ];

      parent::query();
    }

    // User scope: Private message sender.
    elseif ($this->options['user_scope'] == 'thunder_private_message_recipient' && !$flag->isGlobal()) {
      $table_data = Views::viewsData()->get($this->table);
      $left_field = $table_data['table']['base']['field'];

      // Join 'recipient' information.
      $recipient = [
        'left_table' => $this->table,
        'left_field' => $left_field,
        'table' => 'message__tpm_recipient',
        'field' => 'entity_id',
        'type' => 'INNER',
      ];
      $recipient_join = $this->joinManager->createInstance('standard', $recipient);
      $recipient_alias = $this->query->addRelationship($recipient['table'] . '_' . $this->table, $recipient_join, $this->definition['base'], $this->relationship);

      // Join 'flag' information.
      $flag = [
        'left_table' => 'message__tpm_recipient',
        'left_field' => 'entity_id',
        'table' => $this->definition['base'],
        'field' => 'entity_id',
        'extra' => [
          [
            'field' => 'flag_id',
            'value' => $flag->id(),
            'numeric' => TRUE,
          ],
          [
            'field' => 'uid',
            'left_table' => 'message__tpm_recipient',
            'left_field' => 'tpm_recipient_target_id',
          ],
        ],
      ];
      $flag_join = $this->joinManager->createInstance('standard', $flag);
      $this->alias = $this->query->addRelationship($this->definition['base'] . '_' . $this->table, $flag_join, $this->definition['base'], $recipient_alias);

      // Add access tags if the base table provide it.
      if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
        $access_tag = $table_data['table']['base']['access query tag'];
        $this->query->addTag($access_tag);
      }
    }
  }

}
