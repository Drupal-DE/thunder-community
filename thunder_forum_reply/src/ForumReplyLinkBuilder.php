<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\thunder_forum_reply\Plugin\Field\FieldType\ForumReplyItemInterface;

/**
 * Defines a class for building forum reply links on a replied entity.
 *
 * Forum reply links include 'log in to post new reply', 'add new reply' etc.
 */
class ForumReplyLinkBuilder implements ForumReplyLinkBuilderInterface {

  use StringTranslationTrait;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Forum reply manager service.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyManagerInterface
   */
  protected $forumReplyManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ForumReplyLinkBuilder object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\thunder_forum_reply\ForumReplyManagerInterface $forum_reply_manager
   *   Forum reply manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(AccountInterface $current_user, ForumReplyManagerInterface $forum_reply_manager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->forumReplyManager = $forum_reply_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRepliedNodeLinks(NodeInterface $node, array &$context) {
    $entity_links = [];
    $view_mode = $context['view_mode'];

    // Do not add any links if the entity is displayed for:
    // - search indexing.
    // - constructing a search result excerpt.
    // - print.
    // - rss.
    if (in_array($view_mode, ['search_index', 'search_result', 'print', 'rss'], TRUE)) {
      return [];
    }

    $fields = $this->forumReplyManager->getFields();

    foreach ($fields as $field_name => $detail) {
      // Skip fields that the entity does not have.
      if (!$node->hasField($field_name)) {
        continue;
      }

      $field_definition = $node->getFieldDefinition($field_name);
      $form_location = $field_definition->getSetting('form_location');

      $links = [];

      // Create basic forum reply object for access checks.
      $reply = $this->entityTypeManager
        ->getStorage('thunder_forum_reply')
        ->create([
          'nid' => $node->id(),
          'field_name' => $field_name,
        ]);

      // Teaser view: display the number of forum replies that have been posted,
      // or a link to add new forum replies if the user has permission and there
      // currently are none.
      if ($view_mode == 'teaser') {
        // Provide a link to view forum replies.
        if ($reply->access('view', $this->currentUser)) {
          if (!empty($node->get($field_name)->reply_count)) {
            $links['thunder-forum-reply-replies'] = [
              'title' => $this->formatPlural($node->get($field_name)->reply_count, '1 reply', '@count replies'),
              'attributes' => [
                'title' => $this->t('Jump to the first reply.'),
              ],
              'fragment' => 'forum-replies',
              'url' => $node->toUrl(),
            ];

            if ($this->moduleHandler->moduleExists('history')) {
              $links['thunder-forum-reply-new-replies'] = [
                'title' => '',
                'url' => Url::fromRoute('<current>'),
                'attributes' => [
                  'class' => 'hidden',
                  'title' => $this->t('Jump to the first new reply.'),
                  'data-history-node-last-thunder-forum-reply-timestamp' => $node->get($field_name)->last_reply_timestamp,
                  'data-history-node-field-name' => $field_name,
                ],
              ];
            }
          }
        }

        // Provide a link to new forum reply form.
        if ($reply->access('create', $this->currentUser)) {
          $links['thunder-forum-reply-add'] = [
            'title' => $this->t('Add new reply'),
            'language' => $node->language(),
            'attributes' => [
              'title' => $this->t('Share your thoughts and opinions.'),
            ],
            'fragment' => 'forum-reply-form',
          ];

          if ($form_location == ForumReplyItemInterface::FORM_SEPARATE_PAGE) {
            $links['thunder-forum-reply-add']['url'] = Url::fromRoute('thunder_forum_reply.add', [
              'node' => $node->id(),
              'field_name' => $field_name,
            ]);
          }
          else {
            $links['thunder-forum-reply-add'] += ['url' => $node->toUrl()];
          }
        }
      }

      // Forum node in other view modes: add a "post forum reply" link if the
      // user is allowed to post forum replies and if this entity is allowing
      // new forum replies.
      else {
        if ($reply->access('create', $this->currentUser)) {
          // Show the "post forum reply" link if the form is on another page, or
          // if there are existing forum replies that the link will skip past.
          if ($form_location == ForumReplyItemInterface::FORM_SEPARATE_PAGE || (!empty($node->get($field_name)->reply_count) && $reply->access('view', $this->currentUser))) {
            $links['thunder-forum-reply-add'] = array(
              'title' => $this->t('Add new reply'),
              'attributes' => [
                'title' => $this->t('Share your thoughts and opinions.'),
              ],
              'fragment' => 'forum-reply-form',
            );

            if ($form_location == ForumReplyItemInterface::FORM_SEPARATE_PAGE) {
              $links['thunder-forum-reply-add']['url'] = Url::fromRoute('thunder_forum_reply.add', [
                'node' => $node->id(),
                'field_name' => $field_name,
              ]);
            }
            else {
              $links['thunder-forum-reply-add']['url'] = $node->urlInfo();
            }
          }
        }
      }

      if (!empty($links)) {
        $entity_links['thunder_forum_reply__' . $field_name] = [
          '#theme' => 'links__entity__thunder_forum_reply__' . $field_name,
          '#links' => $links,
          '#attributes' => array('class' => array('links', 'inline')),
        ];

        if ($view_mode === 'teaser' && $this->moduleHandler->moduleExists('history') && $this->currentUser->isAuthenticated()) {
          $entity_links['thunder_forum_reply__' . $field_name]['#cache']['contexts'][] = 'user';
          $entity_links['thunder_forum_reply__' . $field_name]['#attached']['library'][] = 'thunder_forum_reply/drupal.node-new-forum-replies-link';

          // Embed the metadata for the "X new forum replies" link (if any) on
          // this forum node.
          $entity_links['thunder_forum_reply__' . $field_name]['#attached']['drupalSettings']['history']['lastReadTimestamps'][$node->id()] = (int) history_read($node->id());

          $new_replies = $this->forumReplyManager->getCountNewReplies($node);

          if ($new_replies > 0) {
            /** @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage */
            $storage = $this->entityTypeManager->getStorage('thunder_forum_reply');

            $page_number = $storage->getNewReplyPageNumber($node->{$field_name}->reply_count, $new_replies, $node, $field_name);
            $query = $page_number ? ['page' => $page_number] : NULL;
            $value = [
              'new_reply_count' => (int) $new_replies,
              'first_new_reply_link' => $node->toUrl('canonical', [
                'query' => $query,
                'fragment' => 'new',
              ]),
            ];
            $parents = ['thunder_forum_reply', 'newRepliesLinks', 'node', $field_name, $node->id()];
            NestedArray::setValue($entity_links['thunder_forum_reply__' . $field_name]['#attached']['drupalSettings'], $parents, $value);
          }
        }
      }
    }

    return $entity_links;
  }

}
