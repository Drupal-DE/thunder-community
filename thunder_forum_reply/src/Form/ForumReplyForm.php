<?php

namespace Drupal\thunder_forum_reply\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the forum reply edit forms.
 */
class ForumReplyForm extends ContentEntityForm {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateForamtter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ForumReplyForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityManagerInterface $entity_manager, DateFormatterInterface $date_formatter, RendererInterface $renderer) {
    parent::__construct($entity_manager);

    $this->dateForamtter = $date_formatter;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);

    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entity;

    $node = $reply->getRepliedNode();

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $this->entityManager->getFieldDefinitions($node->getEntityTypeId(), $node->bundle())[$reply->getFieldName()];

    $preview_mode = $field_definition->getSetting('preview');

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';

    // Only show the save button if forum reply previews are optional or if we
    // are already previewing the submission.
    $element['submit']['#access'] = $this->currentUser()->hasPermission('administer forums') || $preview_mode != DRUPAL_REQUIRED || $form_state->get('thunder_forum_reply_preview');

    $element['preview'] = [
      '#type' => 'submit',
      '#access' => $preview_mode != DRUPAL_DISABLED,
      '#value' => $this->t('Preview'),
      '#weight' => 20,
      '#submit' => ['::submitForm', '::preview'],
    ];

    // Only show 'Cancel' button if in preview.
    $element['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => $reply->getRepliedNode()->toUrl(),
      '#access' => $reply->inPreview ? TRUE : FALSE,
      '#weight' => 200,
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    $element['delete']['#access'] = $reply->access('delete');
    $element['delete']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('date.formatter'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $reply \Drupal\thunder_forum_reply\ForumReplyInterface */
    $reply = $this->entity;

    $node = $reply->getRepliedNode();

    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
    $field_definition = $this->entityManager->getFieldDefinitions($node->getEntityTypeId(), $node->bundle())[$reply->getFieldName()];

    // Set up cache.
    $form['#cache']['contexts'][] = 'user.permissions';
    $this->renderer->addCacheableDependency($form, $field_definition->getConfig($node->bundle()));

    // Use #forum-reply-form as unique jump target, regardless of entity type.
    $form['#id'] = Html::getUniqueId('forum_reply_form');
    $form['#theme'] = [
      'forum_reply_form__' . $node->getEntityTypeId() . '__' . $node->bundle() . '__' . $reply->getFieldName(),
      'forum_reply_form',
    ];

    if ($this->currentUser()->isAuthenticated()) {
      $form['#attached']['library'][] = 'core/drupal.form';
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }

    // Use dedicated page callback for new forum replies on entities.
    if ($reply->isNew() && !$reply->hasParentReply()) {
      if ($reply->getShouldContainParentQuoteOnCreate()) {
        $form['#action'] = Url::fromRoute('thunder_forum_reply.quote', [
          'node' => $node->id(),
          'field_name' => $reply->getFieldName(),
        ])->toString();
      }
      else {
        $form['#action'] = Url::fromRoute('thunder_forum_reply.add', [
          'node' => $node->id(),
          'field_name' => $reply->getFieldName(),
        ])->toString();
      }
    }

    // Is in preview?
    $reply_preview = $form_state->get('thunder_forum_reply_preview');
    if (isset($reply_preview)) {
      $form += $reply_preview;
    }

    // Different title on edit.
    if ($this->operation === 'edit') {
      $form['#title'] = $this->t('Edit forum reply %title', ['%title' => $reply->label()]);
    }

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $reply->getChangedTime(),
    ];

    // Merge form structure build by parent.
    $form = parent::form($form, $form_state);

    // Display author information.
    if (!$reply->isNew()) {
      $account = $reply->getOwner();

      $username = [
        '#theme' => 'username',
        '#account' => $account,
      ];

      $form['submitted'] = [
        '#markup' => $this->t('Submitted by @username on @datetime', [
          '@username' => $this->renderer->render($username),
          '@datetime' => $this->dateForamtter->format($reply->getCreatedTime()),
        ]),
        '#weight' => -100,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entity;

    if (!$reply->isNew()) {
      // Remove the revision log message from the original forum reply entity.
      $reply->revision_log = NULL;
    }

    else {
      // Set default subject.
      if (!$reply->getSubject()) {
        $reply->setSubject($reply->getDefaultSubject());
      }

      // Set quote text of parent (if any).
      if ($reply->getShouldContainParentQuoteOnCreate() && ($quote = $reply->getParentQuoteText())) {
        $reply->set('body', '<blockquote>' . $quote . '</blockquote>');
      }
    }
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function preview(array $form, FormStateInterface $form_state) {
    $build = [];

    if (!$form_state->getErrors()) {
      $this->entity->inPreview = TRUE;
      $build['reply_preview'] = $this->entityTypeManager
        ->getViewBuilder('thunder_forum_reply')
        ->view($this->entity);

      $build['reply_preview']['#weight'] = -1000;
    }

    $form_state->set('thunder_forum_reply_preview', $build);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entity;

    // Is new?
    $insert = $reply->isNew();

    $node = $reply->getRepliedNode();
    $field_name = $reply->getFieldName();
    $logger = $this->logger('thunder_forum_reply');

    if (($insert && $reply->access('create', $this->currentUser())) || (!$insert && $reply->access('update', $this->currentUser()))) {
      // Save reply.
      $reply->save();

      $reply_link = $reply->toLink($this->t('View'));

      $logger_context = [
        '@type' => $reply->getEntityTypeId(),
        '%title' => $reply->label(),
        'link' => $reply_link->toString(),
      ];

      $t_args = [
        '@type' => $reply->getEntityType()->getLabel(),
        '%title' => $reply->toLink($reply->label())->toString(),
      ];

      // Log action / display message to user.
      if ($insert) {
        $logger->notice('@type: added %title.', $logger_context);
        drupal_set_message($this->t('@type %title has been created.', $t_args));
      }
      else {
        $logger->notice('@type: updated %title.', $logger_context);
        drupal_set_message($this->t('@type %title has been updated.', $t_args));
      }

      $query = [];
      // Find the current display page for this forum reply.
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $this->entityManager->getFieldDefinitions($node->getEntityTypeId(), $node->bundle())[$field_name];

      /** @var \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage */
      $storage = $this->entityManager
        ->getStorage('thunder_forum_reply');

      $page = $storage->getDisplayOrdinal($reply, $field_definition->getSetting('per_page'));

      if ($page > 0) {
        $query['page'] = $page;
      }

      // Redirect to the newly posted forum reply (if access is allowed).
      $url = Url::fromRoute('<front>');

      if ($reply->access('view', $this->currentUser())) {
        $url = $reply->toUrl();
      }

      elseif ($reply->getRepliedNode()->access('view', $this->currentUser())) {
        $url = $reply->getRepliedNode()->toUrl();
      }

      $form_state->setRedirectUrl($url
        ->setOption('query', $query)
        ->setOption('fragment', 'forum-reply-' . $reply->id()));
    }

    else {
      // Log warning / display error to user.
      $logger->warning('@type: unauthorized forum reply %title submitted or forum reply submitted to a closed post.', [
        '@type' => $reply->getEntityTypeId(),
        '%title' => $reply->label(),
      ]);
      drupal_set_message($this->t('@type: unauthorized forum reply %title submitted or forum reply submitted to a closed post.', [
        '@type' => $reply->getEntityTypeId(),
        '%title' => $reply->label(),
      ]), 'error');

      // In the unlikely case something went wrong on save, the forum reply will
      // be rebuilt and forum reply form redisplayed the same way as in preview.
      $form_state->setRebuild();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Updates the forum reply object by processing the submitted values.
   *
   * This function can be called by a "Next" button of a wizard to update the
   * form state's entity with the current step's values before proceeding to the
   * next step.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build the forum reply object from the submitted values.
    parent::submitForm($form, $form_state);

    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface $reply */
    $reply = $this->entity;

    // Always save as a new revision.
    $reply->setNewRevision();
    $reply->setRevisionCreationTime(REQUEST_TIME);
    $reply->setRevisionUserId($this->currentUser()->id());
  }

}
