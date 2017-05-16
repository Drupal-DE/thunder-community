<?php

namespace Drupal\thunder_forum_access\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides forum access manager service.
 */
class ForumAccessManager implements ForumAccessManagerInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The forum access record storage.
   *
   * @var \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface
   */
  protected $forumAccessRecordStorage;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new ForumAccessManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface $forum_access_record_storage
   *   The forum access record storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ForumAccessRecordStorageInterface $forum_access_record_storage, AccountInterface $current_user, RedirectDestinationInterface $redirect_destination) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->forumAccessRecordStorage = $forum_access_record_storage;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForumNodeForm(array &$form, FormStateInterface $form_state, $form_id) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_state->getFormObject()->getEntity();

    // Is a forum content form?
    if ($this->forumAccessRecordStorage->getForumManagerService()->checkNodeType($node)) {
      // Content is not new and forum field is not empty?
      if (!$node->isNew() && !$node->get('taxonomy_forums')->isEmpty()) {
        // Only show 'Leave shadow copy' field for moderator/admin users.
        if (isset($form['shadow'])) {
          $form['shadow']['#access'] = $this->userIsForumModerator($node->get('taxonomy_forums')->first()->entity->id(), $this->currentUser);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterForumTermForm(array &$form, FormStateInterface $form_state, $form_id) {
    // Is forum taxonomy term form?
    if ($this->forumAccessRecordStorage->getForumManagerService()->isForumTermForm($form_id)) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $form_state->getFormObject()->getEntity();

      // Forum taxonomy term is not new?
      if (!$term->isNew()) {
        // Current user is forum administrator?
        $is_admin = $this->userIsForumAdmin($this->currentUser);

        // Only show 'Parent' field for admin users.
        if (isset($form['parent'][0])) {
          $form['parent'][0]['#access'] = $is_admin;
        }

        // Only show 'Weight' field for admin users.
        if (isset($form['weight'])) {
          $form['weight']['#access'] = $is_admin;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterForumTermOverviewForm(array &$form, FormStateInterface $form_state, $form_id) {
    if (isset($form['terms'])) {
      // Add 'Access' header column.
      $form['terms']['#header'][] = $this->t('Access');

      // Ensure 'Operations' header column is last.
      $operations_header = NULL;
      foreach ($form['terms']['#header'] as $key => $header) {
        if ($header instanceof TranslatableMarkup && (string) $header === (string) $this->t('Operations')) {
          $operations_header = $header;
          unset($form['terms']['#header'][$key]);
        }
      }

      if (isset($operations_header)) {
        $form['terms']['#header'][] = $operations_header;
      }

      foreach (Element::children($form['terms']) as $key) {
        if (isset($form['terms'][$key]['#term'])) {
          // Add 'configure access' operation link to all terms.
          $form['terms'][$key]['operations']['#links']['thunder_forum_access']['title'] = $this->t('configure access');
          $form['terms'][$key]['operations']['#links']['thunder_forum_access']['url'] = Url::fromRoute('entity.taxonomy_term.thunder_forum_access', ['taxonomy_term' => $form['terms'][$key]['#term']->id()]);
          $form['terms'][$key]['operations']['#links']['thunder_forum_access']['query'] = $this->redirectDestination->getAsArray();

          // Load forum access record.
          $record = $this->getForumAccessRecord($form['terms'][$key]['#term']->id());

          // Add column containing forum access information.
          $form['terms'][$key]['access'] = [
            '#markup' => $record->inheritsMemberUserIds() && $record->inheritsModeratorUserIds() && $record->inheritsPermissions() ? $this->t('Inherited') : $this->t('Custom'),
          ];
        }

        // Ensure 'Operations' column is last.
        $operations = $form['terms'][$key]['operations'];
        unset($form['terms'][$key]['operations']);
        $form['terms'][$key]['operations'] = $operations;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForumAccessRecord($tid) {
    $record = $this->forumAccessRecordStorage
      ->accessRecordLoad($tid);

    if (!$record) {
      throw new \Exception('Forum access record not available.');
    }

    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getForumMembers($tid) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($tid)->getMemberUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getForumModerators($tid) {
    return $this->entityTypeManager
      ->getStorage('user')
      ->loadMultiple($this->getForumAccessRecord($tid)->getModeratorUserIds());
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumAdmin(AccountInterface $account) {
    return $this->getForumAccessRecord(0)->userIsForumAdmin($account);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumMember($tid, AccountInterface $account) {
    if ($this->userIsForumAdmin($account)) {
      return TRUE;
    }

    $members = $this->getForumAccessRecord($tid)->getMemberUserIds();

    return isset($members[$account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsForumModerator($tid, AccountInterface $account) {
    if ($this->userIsForumAdmin($account)) {
      return TRUE;
    }

    $moderators = $this->getForumAccessRecord($tid)->getModeratorUserIds();

    return isset($moderators[$account->id()]);
  }

}
