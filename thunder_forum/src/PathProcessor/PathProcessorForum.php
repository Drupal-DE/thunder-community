<?php

namespace Drupal\thunder_forum\PathProcessor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes inbound/outbound forum-related taxonomy term paths.
 */
class PathProcessorForum implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * Path pattern: Taxonomy term.
   */
  const PATH_PATTERN_TAXONOMY_TERM = '!/taxonomy/term/(\d+)$!';

  /**
   * Path pattern: Add taxonomy term.
   */
  const PATH_PATTERN_TAXONOMY_TERM_ADD = '!/admin/structure/taxonomy/manage/forums/add$!';

  /**
   * Path pattern: Delete taxonomy term.
   */
  const PATH_PATTERN_TAXONOMY_TERM_DELETE = '!/taxonomy/term/(\d+)/delete!';

  /**
   * Path pattern: Edit taxonomy term.
   */
  const PATH_PATTERN_TAXONOMY_TERM_EDIT = '!/taxonomy/term/(\d+)/edit$!';

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PathProcessorForumTerm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving the forum configuration.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns TRUE if the given taxonomy term is a forum container.
   *
   * This is a verbatim copy of ThunderForumManagerInterface::isForumContainer()
   * because the forum manager service must not be used here, as it results in
   * a circular service reference loop.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum container.
   *
   * @see \Drupal\thunder_forum\ThunderForumManagerInterface::isForumContainer()
   */
  public function isForumContainer(TermInterface $term) {
    return $this->isForumTerm($term) && $term->hasField('forum_container') && !empty($term->forum_container->value);
  }

  /**
   * Returns TRUE if the given taxonomy term is a forum term.
   *
   * This is a verbatim copy of ThunderForumManagerInterface::isForumTerm()
   * because the forum manager service must not be used here, as it results in
   * a circular service reference loop.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   A taxonomy term.
   *
   * @return bool
   *   Boolean indicating whether the given taxonomy term is a forum term.
   *
   * @see \Drupal\thunder_forum\ThunderForumManagerInterface::isForumTerm()
   */
  protected function isForumTerm(TermInterface $term) {
    return $term->bundle() === $this->configFactory->get('forum.settings')->get('vocabulary');
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    // Rewrite forum-related taxonomy term paths.
    $this->rewriteForumTermPath($path);

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Rewrite forum-related taxonomy term paths.
    if (($term = $this->rewriteForumTermPath($path, static::PATH_PATTERN_TAXONOMY_TERM)) && $bubbleable_metadata) {
      $bubbleable_metadata->addCacheableDependency($term);
    }

    return $path;
  }

  /**
   * Rewrite forum-related taxonomy term paths.
   *
   * @param string $path
   *   An internal path.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The forum taxonomy term (if available), otherwise NULL.
   */
  protected function rewriteForumTermPath(&$path) {
    // Prepare list of forum-related taxonomy term path patterns.
    $patterns = [
      static::PATH_PATTERN_TAXONOMY_TERM,
      static::PATH_PATTERN_TAXONOMY_TERM_ADD,
      static::PATH_PATTERN_TAXONOMY_TERM_DELETE,
      static::PATH_PATTERN_TAXONOMY_TERM_EDIT,
    ];

    // Process patterns and rewrite matching paths.
    foreach ($patterns as $pattern) {
      $matches = [];

      if (preg_match($pattern, $path, $matches)) {
        // Taxonomy term add path.
        if ($pattern === static::PATH_PATTERN_TAXONOMY_TERM_ADD) {
          $path = '/' . ltrim(Url::fromRoute('forum.add_forum')->getInternalPath(), '/');
        }

        // Taxonomy term paths with term object context.
        else {
          /** @var \Drupal\taxonomy\TermInterface $term */
          $term = $this->entityTypeManager
            ->getStorage('taxonomy_term')
            ->load($matches[1]);

          // Is forum taxonomy term?
          if ($this->isForumTerm($term)) {
            switch ($pattern) {
              case static::PATH_PATTERN_TAXONOMY_TERM:
                // Taxonomy term path.
                $path = '/' . ltrim(forum_uri($term)->getInternalPath(), '/');
                break;

              case static::PATH_PATTERN_TAXONOMY_TERM_DELETE:
                // Taxonomy term delete path.
                $path = '/' . ltrim($term->toUrl('forum-delete-form')->getInternalPath(), '/');
                break;

              case static::PATH_PATTERN_TAXONOMY_TERM_EDIT:
                // Taxonomy term edit path.
                $path = '/' . ltrim($term->toUrl($this->isForumContainer($term) ? 'forum-edit-container-form' : 'forum-edit-form')->getInternalPath(), '/');
                break;

              default:
                return NULL;
            }

            return $term;
          }
        }
      }
    }

    return NULL;
  }

}
