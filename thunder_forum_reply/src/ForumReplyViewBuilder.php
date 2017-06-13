<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for forum replies.
 */
class ForumReplyViewBuilder extends EntityViewBuilder {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ForumReplyViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, AccountInterface $current_user) {
    parent::__construct($entity_type, $entity_manager, $language_manager);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    parent::alterBuild($build, $entity, $display, $view_mode);

    if (empty($entity->inPreview)) {
      // Add anchor for each forum reply.
      $build['#prefix'] = "<a id=\"forum-reply-{$entity->id()}\"></a>\n";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    // Pre-load associated users into cache to leverage multiple loading.
    $uids = [];
    foreach ($entities as $entity) {
      $uids[] = $entity->getOwnerId();
    }

    $this->entityManager->getStorage('user')->loadMultiple(array_unique($uids));

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      // Replied entities already loaded after self::getBuildDefaults().
      $replied_node = $entity->getRepliedNode();

      $display = $displays[$entity->bundle()];

      $build[$id]['#entity'] = $entity;
      $build[$id]['#theme'] = 'thunder_forum_reply__' . $entity->getFieldName() . '__' . $replied_node->bundle();

      // Links.
      if ($display->getComponent('links')) {
        $build[$id]['links'] = [
          '#lazy_builder' => [
            'thunder_forum_reply.lazy_builders:renderLinks',
            [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->inPreview),
            ],
          ],
          '#create_placeholder' => TRUE,
        ];
      }

      // Links (header).
      if ($display->getComponent('links_header')) {
        $build[$id]['links_header'] = [
          '#lazy_builder' => [
            'thunder_forum_reply.lazy_builders:renderLinks',
            [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->inPreview),
              'header',
            ],
          ],
          '#create_placeholder' => TRUE,
        ];
      }

      // Links (footer).
      if ($display->getComponent('links_footer')) {
        $build[$id]['links_footer'] = [
          '#lazy_builder' => [
            'thunder_forum_reply.lazy_builders:renderLinks',
            [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->inPreview),
              'footer',
            ],
          ],
          '#create_placeholder' => TRUE,
        ];
      }

      if (!isset($build[$id]['#attached'])) {
        $build[$id]['#attached'] = [];
      }

      $build[$id]['#attached']['library'][] = 'thunder_forum_reply/drupal.forum-reply-by-viewer';

      if ($this->moduleHandler()->moduleExists('history') && $this->currentUser->isAuthenticated()) {
        $build[$id]['#attached']['library'][] = 'thunder_forum_reply/drupal.forum-reply-new-indicator';

        // Embed the metadata for the forum reply "new" indicators on this node.
        $build[$id]['history'] = [
          '#lazy_builder' => ['history_attach_timestamp', [$replied_node->id()]],
          '#create_placeholder' => TRUE,
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    // Don't cache forum replies that are in 'preview' mode.
    if (isset($defaults['#cache']) && isset($entity->inPreview)) {
      unset($defaults['#cache']);
    }

    return $defaults;
  }

}
