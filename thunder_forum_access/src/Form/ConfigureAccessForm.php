<?php

namespace Drupal\thunder_forum_access\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_forum\ThunderForumManagerInterface;
use Drupal\thunder_forum_access\Access\ForumAccessRecordInterface;
use Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a forum access configuration form.
 */
class ConfigureAccessForm extends FormBase {

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
   * The forum manager.
   *
   * @var \Drupal\thunder_forum\ThunderForumManagerInterface
   */
  protected $forumManager;

  /**
   * Constucts a new ConfigureAccessForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\thunder_forum\ThunderForumManagerInterface $forum_manager
   *   The forum manager.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordStorageInterface $forum_access_record_storage
   *   The forum access storage.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ThunderForumManagerInterface $forum_manager, ForumAccessRecordStorageInterface $forum_access_record_storage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->forumAccessRecordStorage = $forum_access_record_storage;
    $this->forumManager = $forum_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('forum_manager'),
      $container->get('thunder_forum_access.forum_access_record_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_forum_access_configure_access';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, TermInterface $taxonomy_term = NULL) {
    // Determine taxonomy term ID (with '0' as fallback for top-level
    // configuration).
    $tid = $taxonomy_term instanceof TermInterface ? $taxonomy_term->id() : 0;

    // Load forum access records.
    $record = $this->forumAccessRecordStorage->accessRecordLoad($tid);
    $record_parent = $this->forumAccessRecordStorage->accessRecordLoad($this->forumManager->getParentId($tid));

    // Set page title.
    $form['#title'] = $taxonomy_term instanceof TermInterface ? $this->t('Configure access: @label', ['@label' => $taxonomy_term->label()]) : $this->t('Configure access');

    // Term ID value.
    $form['tid'] = [
      '#type' => 'value',
      '#value' => $tid,
    ];

    // Vertical tabs.
    $form['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
      '#attached' => [
        'library' => ['thunder_forum_access/form'],
      ],
    ];

    // Permissions.
    $this->buildFormPermissions($form, $form_state, $record, $record_parent);

    // Members.
    $this->buildFormMembers($form, $form_state, $record, $record_parent);

    // Moderators.
    $this->buildFormModerators($form, $form_state, $record, $record_parent);

    // Actions wrapper.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Submit button.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * Form constructor: Members.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record_parent
   *   The forum access record of the forum's parent.
   */
  protected function buildFormMembers(array &$form, FormStateInterface $form_state, ForumAccessRecordInterface $record, ForumAccessRecordInterface $record_parent) {
    // Vertical tab: Members.
    $form['members'] = [
      '#type' => 'details',
      '#title' => $this->t('Members'),
      '#group' => 'vertical_tabs',
      '#attributes' => [
        'class' => ['thunder-forum-access-form-members'],
      ],
    ];

    // Inherit forum members.
    $form['members']['inherit_members'] = [
      '#type' => 'checkbox',
      '#title' => 'Inherit members',
      '#description' => $this->t("If selected, the forum member list is inherited by one of the forum's parents (if any)."),
      '#default_value' => $record->inheritsMemberUserIds(),
      '#access' => $record->getTermId() > 0,
    ];

    // Members.
    $form['members']['members'] = $this->userReferenceElement('members', $form_state, $record->getMemberUserIds());

    // Members (inherited - read-only).
    if ($record->getTermId()) {
      $form['members']['members_inherited'] = $this->userReferenceElement('members', $form_state, $record_parent->getMemberUserIds(), TRUE);
    }
  }

  /**
   * Form constructor: Moderators.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record_parent
   *   The forum access record of the forum's parent.
   */
  protected function buildFormModerators(array &$form, FormStateInterface $form_state, ForumAccessRecordInterface $record, ForumAccessRecordInterface $record_parent) {
    // Vertical tab: Moderators.
    $form['moderators'] = [
      '#type' => 'details',
      '#title' => $this->t('Moderators'),
      '#group' => 'vertical_tabs',
      '#attributes' => [
        'class' => ['thunder-forum-access-form-moderators'],
      ],
    ];

    // Inherit forum moderators.
    $form['moderators']['inherit_moderators'] = [
      '#type' => 'checkbox',
      '#title' => 'Inherit moderators',
      '#description' => $this->t("If selected, the forum moderator list is inherited by one of the forum's parents (if any)."),
      '#default_value' => $record->inheritsModeratorUserIds(),
      '#access' => $record->getTermId() > 0,
    ];

    // Moderators.
    $form['moderators']['moderators'] = $this->userReferenceElement('moderators', $form_state, $record->getModeratorUserIds());

    // Moderators (inherited - read-only).
    if ($record->getTermId()) {
      $form['moderators']['moderators_inherited'] = $this->userReferenceElement('moderators', $form_state, $record_parent->getModeratorUserIds(), TRUE);
    }
  }

  /**
   * Form constructor: Permissions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record
   *   The forum access record.
   * @param \Drupal\thunder_forum_access\Access\ForumAccessRecordInterface $record_parent
   *   The forum access record of the forum's parent.
   */
  protected function buildFormPermissions(array &$form, FormStateInterface $form_state, ForumAccessRecordInterface $record, ForumAccessRecordInterface $record_parent) {
    // Vertical tab: Permissions.
    $form['permissions'] = [
      '#type' => 'details',
      '#title' => $this->t('Permissions'),
      '#group' => 'vertical_tabs',
      '#attributes' => [
        'class' => ['thunder-forum-access-form-permissions'],
      ],
    ];

    // Inherit forum permissions.
    $form['permissions']['inherit_permissions'] = [
      '#type' => 'checkbox',
      '#title' => 'Inherit permissions',
      '#description' => $this->t("If selected, the forum permissions are inherited by one of the forum's parents (if any)."),
      '#default_value' => $record->inheritsPermissions(),
      '#access' => $record->getTermId() > 0,
      '#group' => 'permissions',
    ];

    // Permissions.
    $form['permissions']['permissions'] = [
      '#type' => 'tfa_access_matrix',
      '#default_value' => $record->getPermissions(),
      '#states' => [
        'visible' => [
          ':input[name="inherit_permissions"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Permissions (inherited - read-only).
    if ($record->getTermId()) {
      $form['permissions']['permissions_inherited'] = [
        '#type' => 'tfa_access_matrix',
        '#default_value' => $record_parent->getPermissions(),
        '#disabled' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="inherit_permissions"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Members.
    $inherit_members = !empty($values['inherit_members']);
    $members = !$inherit_members && !empty($values['members']['items']) ? array_unique($values['members']['items']) : [];

    // Moderators.
    $inherit_moderators = !empty($values['inherit_moderators']);
    $moderators = !$inherit_moderators && !empty($values['moderators']['items']) ? array_unique($values['moderators']['items']) : [];

    // Permissions.
    $inherit_permissions = !empty($values['inherit_permissions']);
    $permissions = !$inherit_permissions && !empty($values['permissions']) ? $values['permissions'] : [];

    // Create, populate and save access record.
    $result = $this->forumAccessRecordStorage->accessRecordCreate($values['tid'])
      ->setMemberUserIds($inherit_members, $members)
      ->setModeratorUserIds($inherit_moderators, $moderators)
      ->setPermissions($inherit_permissions, $permissions)
      ->save();

    // Display feedback to user.
    if ($result) {
      drupal_set_message($this->t('The forum access record has been saved.'));
    }
    else {
      drupal_set_message($this->t('Unable to save forum access record.'), 'error');
    }

    // Redirect to forum overview (if not top-level access configuration).
    if (!empty($values['tid'])) {
      $form_state->setRedirect('forum.page', ['taxonomy_term' => $values['tid']]);
    }
  }

  /**
   * Return user reference element.
   *
   * @param string $name
   *   The name of the user reference element in the Form API structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param int[] $uids
   *   An array of user IDs to use as element default value.
   * @param bool $readonly
   *   Whether to return a read-only element.
   *
   * @return array
   *   The form element.
   */
  protected function userReferenceElement($name, FormStateInterface $form_state, array $uids, $readonly = FALSE) {
    // Set up field properties.
    switch ($name) {
      case 'members':
        $title = $this->t('Members');
        $description = $this->t('The list of users that are members in this forum and its subforums (if not configured differently on another level)..');
        $button_label = $this->t('Add member');
        break;

      case 'moderators';
        $title = $this->t('Moderators');
        $description = $this->t('The list of users that are moderators in this forum and its subforums (if not configured differently on another level)..');
        $button_label = $this->t('Add moderator');
        break;

      default:
        throw new \InvalidArgumentException('The passed in user reference field name is invalid.');
    }

    // Load user accounts for entity reference field default value.
    /** @var \Drupal\user\UserStorageInterface $user_storage */
    $user_storage = $this->entityTypeManager
      ->getStorage('user');

    $accounts = $uids ? $user_storage->loadMultiple($uids) : [];

    // Sort user accounts by name.
    usort($accounts, function (AccountInterface $a, AccountInterface $b) {
      return strcmp($a->label(), $b->label());
    });

    // Determine identifier for the number of items.
    $key_num_items = $this->userReferenceElementNumItemsKey($name);

    // Set up number of items.
    $num_items = $readonly ? count($accounts) : $form_state->get($key_num_items);
    if (empty($num_items)) {
      $num_items = count($uids) + 1;
      $form_state->set($key_num_items, $num_items);
    }

    // Generate unique wrapper ID.
    $wrapper_id = 'thunder-forum-access-user-reference-' . $name;

    // Wrapper element.
    $element = [
      '#type' => 'item',
      '#title' => $title,
      '#description' => $description,
      '#description_display' => 'before',
      '#tree' => TRUE,
      '#disabled' => $readonly,
      '#states' => [
        'visible' => [
          ':input[name="inherit_' . $name . '"]' => ['checked' => $readonly],
        ],
      ],
    ];

    // Items container.
    $element['items'] = [
      '#type' => 'container',
      '#id' => $wrapper_id,
    ];

    // Build entity reference fields.
    for ($i = 0; $i < $num_items; $i++) {
      $element['items'][$i] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#default_value' => !empty($accounts[$i]) ? $accounts[$i] : '',
        '#tags' => FALSE,
        '#selection_settings' => [
          'include_anonymous' => FALSE,
        ],
      ];
    }

    // Actions wrapper.
    $element['actions'] = [
      '#type' => 'actions',
      '#access' => $readonly === FALSE,
    ];

    // 'Add more' button.
    $element['actions']['add'] = [
      '#type' => 'submit',
      '#value' => $button_label,
      '#submit' => ['::userReferenceElementAddItemCallback'],
      '#ajax' => [
        'callback' => '::userReferenceElementAddMoreCallback',
        'wrapper' => $wrapper_id,
      ],
    ];

    return $element;
  }

  /**
   * Ajax submit callback; Increases number of fields in user reference element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function userReferenceElementAddItemCallback(array &$form, FormStateInterface $form_state) {
    // Determine triggering element.
    $trigger = $form_state->getTriggeringElement();

    // Determine identifier for the number of items.
    $key_num_items = $this->userReferenceElementNumItemsKey($trigger['#parents'][0]);

    // Increase number of items.
    $num_items = $form_state->get($key_num_items);
    $num_items += 1;
    $form_state->set($key_num_items, $num_items);

    // Force rebuild.
    $form_state->setRebuild();
  }

  /**
   * Ajax callback; Adds another field to a user reference element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function userReferenceElementAddMoreCallback(array &$form, FormStateInterface $form_state) {
    // Determine triggering element.
    $trigger = $form_state->getTriggeringElement();
    $key = $trigger['#parents'][0];

    return $form[$key][$key]['items'];
  }

  /**
   * Return user reference element indentifier for the number of items.
   *
   * @param string $name
   *   The name of the user reference element in the Form API structure.
   *
   * @return string
   *   The identifier.
   */
  protected function userReferenceElementNumItemsKey($name) {
    return '__tfa_user_reference_element_num_items__' . $name;
  }

}
