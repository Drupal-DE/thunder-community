<?php

namespace Drupal\thunder_forum_reply\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default forum reply formatter.
 *
 * @FieldFormatter(
 *   id = "thunder_forum_reply_default",
 *   module = "thunder_forum",
 *   label = @Translation("Forum replies"),
 *   field_types = {
 *     "thunder_forum_reply"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ForumReplyDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The forum reply storage.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The forum reply render controller.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new ForumReplyDefaultFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->currentUser = $current_user;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->storage = $entity_type_manager->getStorage('thunder_forum_reply');
    $this->viewBuilder = $entity_type_manager->getViewBuilder('thunder_forum_reply');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'pager_id' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $settings = $this->getFieldSettings();

    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $items->getEntity();

    // Create dummy forum reply with necessary values for access checking.
    $reply = $this->storage->create([
      'nid' => $entity->id(),
      'field_name' => $field_name,
    ]);

    // Replies thread.
    $replies_per_page = $settings['per_page'];

    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface[] $replies */
    $replies = $reply->access('view', $this->currentUser) ? $this->storage->loadThread($entity, $field_name, $replies_per_page, $this->getSetting('pager_id')) : [];

    // Filter out non-accessible forum replies.
    foreach ($replies as $key => $reply) {
      if (!$reply->access('view', $this->currentUser)) {
        unset($replies[$key]);
      }
    }

    if ($replies) {
      $build = $this->viewBuilder->viewMultiple($replies, $this->getSetting('view_mode'));

      $build['pager']['#type'] = 'pager';

      // ForumReplyController::forumReplyPermalink() calculates the page number
      // where a specific forum reply appears and does a subrequest pointing to
      // that page, we need to pass that subrequest route to our pager to
      // keep the pager working.
      $build['pager']['#route_name'] = $this->routeMatch->getRouteObject();
      $build['pager']['#route_parameters'] = $this->routeMatch->getRawParameters()->all();

      if ($this->getSetting('pager_id')) {
        $build['pager']['#element'] = $this->getSetting('pager_id');
      }

      $output['replies'] = [];
      $output['replies'] += $build;
    }

    // Append forum reply form (if access is granted and the form is set to
    // display below the entity). Do not show the form for the print view mode.
    if ($reply->access('create', $this->currentUser) && $settings['form_location'] == ForumReplyItemInterface::FORM_BELOW && $this->viewMode != 'print') {
      $elements['#cache']['contexts'][] = 'user';

      $output['reply_form'] = [
        '#lazy_builder' => [
          'thunder_forum_reply.lazy_builders:renderForm',
          [$entity->id(), $field_name],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    // Ensure elements.
    $elements[] = $output + [
      'replies' => [],
      'reply_form' => [],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    // Load view mode list.
    $view_modes = $this->getViewModes();

    // View mode.
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Forum reply view mode'),
      '#description' => $this->t('Select the view mode used to show the list of forum replies.'),
      '#default_value' => $this->getSetting('view_mode'),
      '#options' => $view_modes,
      // Only show the select element when there are more than one options.
      '#access' => count($view_modes) > 1,
    ];

    // Pager ID.
    $element['pager_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Pager ID'),
      '#options' => range(0, 10),
      '#default_value' => $this->getSetting('pager_id'),
      '#description' => $this->t("Unless you're experiencing problems with pagers related to this field, you should leave this at 0. If using multiple pagers on one page you may need to set this number to a higher value so as not to conflict within the ?page= array. Large values will add a lot of commas to your URLs, so avoid if possible."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $view_mode = $this->getSetting('view_mode');
    $view_modes = $this->getViewModes();
    $view_mode_label = isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : 'default';

    // View mode.
    $summary = [$this->t('Forum reply view mode: @mode', ['@mode' => $view_mode_label])];

    // Pager ID.
    if ($pager_id = $this->getSetting('pager_id')) {
      $summary[] = $this->t('Pager ID: @id', ['@id' => $pager_id]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    if ($mode = $this->getSetting('view_mode')) {
      /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
      if ($display = $this->entityTypeManager->getStorage('entity_view_display')->load('thunder_forum_reply.thunder_forum_reply.' . $mode)) {
        $dependencies[$display->getConfigDependencyKey()][] = $display->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * Provides a list of forum reply view modes.
   *
   * @return array
   *   Associative array keyed by view mode key and having the view mode label
   *   as value.
   */
  protected function getViewModes() {
    return $this->entityDisplayRepository->getViewModeOptions('thunder_forum_reply');
  }

}
