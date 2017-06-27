<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Defines a service for forum reply #lazy_builder callbacks.
 */
class ForumReplyLazyBuilders implements ForumReplyLazyBuildersInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ForumReplyLazyBuilders object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user, ModuleHandlerInterface $module_handler, RendererInterface $renderer, RedirectDestinationInterface $redirect_destination) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->redirectDestination = $redirect_destination;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function renderForm($nid, $field_name) {
    $values = [
      'nid' => $nid,
      'field_name' => $field_name,
      'pfrid' => NULL,
    ];

    // Create basic forum reply object.
    $reply = $this->entityTypeManager->getStorage('thunder_forum_reply')->create($values);

    return $this->entityFormBuilder->getForm($reply);
  }

  /**
   * {@inheritdoc}
   */
  public function renderIcon($frid) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('thunder_forum_reply')
      ->load($frid);

    // Build forum icon.
    $build = [
      '#theme' => 'thunder_forum_reply_icon',
      '#entity' => $entity,
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 0,
      ],
    ];

    // Add entity to cache metadata.
    if ($entity) {
      CacheableMetadata::createFromRenderArray($build)
        ->addCacheableDependency($entity)
        ->applyTo($build);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function renderLinks($reply_entity_id, $view_mode, $langcode, $is_in_preview, $location = NULL) {
    $links = [
      '#theme' => 'links__thunder_forum_reply' . (!empty($location) ? '__' . $location : ''),
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
      $reply = $this->entityTypeManager->getStorage('thunder_forum_reply')
        ->load($reply_entity_id);

      $node = $reply->getRepliedNode();

      $links['thunder_forum_reply'] = $this->buildLinks($reply, $node);

      // Set up cache metadata.
      CacheableMetadata::createFromRenderArray($links)
        ->addCacheTags($reply->getCacheTags())
        ->addCacheContexts($reply->getCacheContexts())
        ->addCacheContexts(['user.permissions'])
        ->addCacheContexts(['user.roles'])
        ->addCacheContexts(['thunder_forum_reply_link_location' . (!empty($location) ? ':' . $location : '')])
        ->mergeCacheMaxAge($reply->getCacheMaxAge())
        ->applyTo($links);

      // Allow other modules to alter the forum reply links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
        'replied_node' => $node,
        'location' => $location,
      ];

      $this->moduleHandler->alter('thunder_forum_reply_links', $links, $reply, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (reply, edit, delete …) for a forum reply.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $reply
   *   The forum reply object.
   * @param \Drupal\node\NodeInterface $node
   *   The forum node to which the forum reply is attached.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum reply links to several
   *   link lists).
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected function buildLinks(ForumReplyInterface $reply, NodeInterface $node, $location = NULL) {
    $links = [];

    if ($reply->access('delete')) {
      $links['thunder_forum_reply-delete'] = [
        'title' => $this->t('Delete'),
        'url' => $reply->toUrl('delete-form')
          ->setOption('query', $this->redirectDestination->getAsArray()),
      ];
    }

    if ($reply->access('update')) {
      $links['thunder_forum_reply-edit'] = [
        'title' => $this->t('Edit'),
        'url' => $reply->toUrl('edit-form'),
      ];
    }

    if ($reply->access('create')) {
      $links['thunder_forum_reply-create'] = [
        'title' => $this->t('Reply'),
        'url' => Url::fromRoute('thunder_forum_reply.add', [
          'node' => $reply->getRepliedNodeId(),
          'field_name' => $reply->getFieldName(),
          'pfrid' => $reply->id(),
        ]),
      ];
    }

    return [
      '#theme' => 'links__thunder_forum_reply__thunder_forum_reply' . (!empty($location) ? '__' . $location : ''),
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

}
