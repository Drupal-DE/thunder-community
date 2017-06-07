<?php

namespace Drupal\thunder_forum\Plugin\views\field;

use Drupal\thunder_forum\ThunderForumManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to display a forum's statistics.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_forum_statistics")
 */
class ForumStatistics extends FieldPluginBase {

  /**
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constructs a ForumStatistics object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
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
    $stats = $this->forumManager->getForumStatistics($tid);

    if ($stats) {
      return [
        '#theme' => 'thunder_forum_statistics',
        '#topic_count' => $stats->topic_count,
        '#reply_count' => $stats->comment_count,
      ];
    }

    return NULL;
  }

}
