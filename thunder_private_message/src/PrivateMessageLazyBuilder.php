<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\FlagServiceInterface;
use Drupal\message\MessageInterface;

/**
 * Defines a service for private message #lazy_builder callbacks.
 */
class PrivateMessageLazyBuilder implements PrivateMessageLazyBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a new PrivateMessageLazyBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateMessageHelperInterface $private_message_helper, FlagServiceInterface $flag_service, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->flagService = $flag_service;
    $this->moduleHandler = $module_handler;
    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * Build the default links (reply, edit, delete...) for a private message.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The forum reply object.
   * @param string|null $location
   *   An optional links location (e.g. to split up forum reply links to several
   *   link lists).
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected function buildLinks(MessageInterface $message, $location = NULL) {
    $links = [];

    // Flag as deleted.
    if ($message->access('flag-deleted')) {
      // Get deleted private message flag.
      $flag = $this->flagService->getFlagById('thunder_private_message_deleted');

      $link_type_plugin = $flag->getLinkTypePlugin();
      $link = $link_type_plugin->getAsLink($flag, $message);

      $links['thunder_private_message-flag-deleted'] = [
        'title' => $link->getText(),
        'url' => $link->getUrl(),
      ];
    }

    // Create reply.
    $reply_access = $this->entityTypeManager
      ->getAccessControlHandler('message')
      ->createAccess('thunder_private_message', NULL, [
        'recipient' => $message->getOwner(),
        'message' => $message,
      ]);

    if (($recipient = $this->privateMessageHelper->getMessageRecipient($message)) && $reply_access) {
      $links['thunder_private_message-reply'] = [
        'title' => $this->t('Reply'),
        'url' => Url::fromRoute('thunder_private_message.add', [
          'user' => $recipient->id(),
          'recipient' => $message->getOwnerId(),
          'message' => $message->id(),
        ]),
      ];
    }

    return [
      '#theme' => 'links__message__thunder_private_message' . (!empty($location) ? '__' . $location : ''),
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderIcon($mid) {
    /** @var \Drupal\message\MessageInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage('message')
      ->load($mid);

    // Build private message icon.
    $build = [
      '#theme' => 'thunder_private_message_icon',
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
  public function renderLinks($message_entity_id, $view_mode, $langcode, $is_in_preview, $location = NULL) {
    $links = [
      '#theme' => 'links__thunder_private_message' . (!empty($location) ? '__' . $location : ''),
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      /** @var \Drupal\message\MessageInterface $message */
      $message = $this->entityTypeManager->getStorage('message')
        ->load($message_entity_id);

      $links['thunder_forum_reply'] = $this->buildLinks($message, $location);

      // Set up cache metadata.
      CacheableMetadata::createFromRenderArray($links)
        ->addCacheableDependency($message)
        ->addCacheContexts(['user.permissions'])
        ->addCacheContexts(['user.roles'])
        ->addCacheContexts(['thunder_private_message_link_location' . (!empty($location) ? ':' . $location : '')])
        ->applyTo($links);

      // Allow other modules to alter the forum reply links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
        'location' => $location,
      ];

      $this->moduleHandler->alter('thunder_private_message_links', $links, $message, $hook_context);
    }

    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function renderUnreadCount($uid) {
    if (!$uid) {
      return [];
    }

    // Load user account.
    $account = $this->entityTypeManager
      ->getStorage('user')
      ->load($uid);

    // Load unread messages count.
    $count = $this->privateMessageHelper->getUnreadCount($account);

    // Build unread messages count.
    $build = [
      '#theme' => 'thunder_private_message_unread_count',
      '#unread_count' => $count,
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    return $build;
  }

}
