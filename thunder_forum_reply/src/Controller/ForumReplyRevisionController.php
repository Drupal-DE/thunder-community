<?php

namespace Drupal\thunder_forum_reply\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\thunder_forum_reply\ForumReplyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller for forum reply revisions.
 */
class ForumReplyRevisionController extends ControllerBase {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateForamtter;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ForumReplyRevisionController.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    $this->dateForamtter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * Displays a forum reply revision.
   *
   * @param int $thunder_forum_reply_revision
   *   The forum reply revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionPage($thunder_forum_reply_revision) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entityTypeManager()->getStorage('thunder_forum_reply')->loadRevision($thunder_forum_reply_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('thunder_forum_reply');

    return $view_builder->view($reply);
  }

  /**
   * Page title callback for a forum reply revision.
   *
   * @param int $thunder_forum_reply_revision
   *   The forum reply revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($thunder_forum_reply_revision) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entityTypeManager()->getStorage('thunder_forum_reply')->loadRevision($thunder_forum_reply_revision);

    return $this->t('Revision of %title from %date', ['%title' => $reply->label(), '%date' => $this->dateForamtter->format($reply->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a forum reply.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $thunder_forum_reply
   *   A forum reply object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function overview(ForumReplyInterface $thunder_forum_reply) {
    $account = $this->currentUser();
    $langcode = $thunder_forum_reply->language()->getId();
    $langname = $thunder_forum_reply->language()->getName();
    $languages = $thunder_forum_reply->getTranslationLanguages();
    $has_translations = (count($languages) > 1);

    /** @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('thunder_forum_reply');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $thunder_forum_reply->label()]) : $this->t('Revisions for %title', ['%title' => $thunder_forum_reply->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission('revert forum reply revisions') || $account->hasPermission('administer forums')));
    $delete_permission = (($account->hasPermission('delete forum reply revisions') || $account->hasPermission('administer forums')));

    $rows = [];

    $vids = $storage->revisionIds($thunder_forum_reply);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $revision */
      $revision = $storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateForamtter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $thunder_forum_reply->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.thunder_forum_reply.revision', ['thunder_forum_reply' => $thunder_forum_reply->id(), 'thunder_forum_reply_revision' => $vid]));
        }
        else {
          $link = $thunder_forum_reply->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link->toString(),
              'username' => $this->renderer->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.thunder_forum_reply.translation_revision_revert', ['thunder_forum_reply' => $thunder_forum_reply->id(), 'thunder_forum_reply_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.thunder_forum_reply.revision_revert', ['thunder_forum_reply' => $thunder_forum_reply->id(), 'thunder_forum_reply_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.thunder_forum_reply.revision_delete', ['thunder_forum_reply' => $thunder_forum_reply->id(), 'thunder_forum_reply_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['thunder_forum_reply_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
