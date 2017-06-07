<?php

namespace Drupal\thunder_forum\Plugin\views\field;

use Drupal\thunder_forum\ThunderForumManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display a forum's last post.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_last_post")
 */
class ForumLastPost extends FieldPluginBase {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a ForumLastPost object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThunderForumManagerInterface $forum_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->forumManager = $forum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('forum_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $tid = $this->getValue($values);
    $last_post = $this->forumManager->getLastPost($tid);

    if ($last_post) {
      return [
        '#theme' => 'thunder_forum_last_post',
        '#created' => $last_post->created,
        '#uid' => $last_post->uid,
        '#entity_id' => $last_post->entity_id,
        '#entity_type_id' => $last_post->entity_type_id,
      ];
    }

    return NULL;
  }

}
