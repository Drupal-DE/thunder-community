<?php

namespace Drupal\thunder_forum_reply\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\thunder_forum_reply\ForumReplyInterface;
use Drupal\thunder_forum_reply\ForumReplyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a forum reply revision.
 */
class ForumReplyRevisionRevertForm extends ConfirmFormBase {

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
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new ForumReplyRevisionRevertForm.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage
   *   The forum reply storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(ForumReplyStorageInterface $storage, DateFormatterInterface $date_formatter) {
    $this->storage = $storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('thunder_forum_reply'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_forum_reply_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())]);
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
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
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
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->save();

    $this->logger('content')->notice('Forum reply: reverted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);

    drupal_set_message(t('Forum reply %title has been reverted to the revision from %revision-date.', ['%title' => $this->revision->label(), '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)]));

    $form_state->setRedirect('entity.thunder_forum_reply.version_history', ['thunder_forum_reply' => $this->revision->id()]);
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\thunder_forum_reply\ForumReplyInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\thunder_forum_reply\ForumReplyInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(ForumReplyInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime(REQUEST_TIME);

    return $revision;
  }

}
