<?php

namespace Drupal\thunder_forum_reply\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\thunder_forum_reply\ForumReplyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a forum reply entity revision.
 */
class ForumReplyRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The forum reply revision.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyInterface
   */
  protected $revision;

  /**
   * The forum reply storage.
   *
   * @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface
   */
  protected $storage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateForamtter;

  /**
   * Constructs a new ForumReplyRevisionDeleteForm.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage
   *   The forum reply storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(ForumReplyStorageInterface $storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->storage = $storage;
    $this->connection = $connection;
    $this->dateForamtter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('thunder_forum_reply'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_forum_reply_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => $this->dateForamtter->format($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.thunder_forum_reply.version_history', ['thunder_forum_reply' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $thunder_forum_reply_revision = NULL) {
    $this->revision = $this->storage->loadRevision($thunder_forum_reply_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->storage->deleteRevision($this->revision->getRevisionId());

    $this->logger('thunder_forum_reply')->notice('Forum reply: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);

    drupal_set_message(t('Revision from %revision-date of forum reply %title has been deleted.', ['%revision-date' => $this->dateForamtter->format($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));

    // Redirect to forum reply by default.
    $form_state->setRedirect('entity.thunder_forum_reply.canonical', ['thunder_forum_reply' => $this->revision->id()]);

    // Redirect to forum reply version history (if more versions are available).
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {thunder_forum_reply_field_revision} WHERE frid = :frid', [':frid' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect('entity.thunder_forum_reply.version_history', ['thunder_forum_reply' => $this->revision->id()]);
    }
  }

}
