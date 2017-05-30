<?php

namespace Drupal\thunder_forum_reply\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\thunder_forum_reply\ForumReplyInterface;
use Drupal\user\UserInterface;

/**
 * Defines the forum reply entity.
 *
 * @ContentEntityType(
 *   id = "thunder_forum_reply",
 *   label = @Translation("Forum reply"),
 *   label_singular = @Translation("forum reply"),
 *   label_plural = @Translation("forum replies"),
 *   label_count = @PluralTranslation(
 *     singular = "@count forum reply",
 *     plural = "@count forum replies"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\thunder_forum_reply\ForumReplyStorage",
 *     "storage_schema" = "Drupal\thunder_forum_reply\ForumReplyStorageSchema",
 *     "view_builder" = "Drupal\thunder_forum_reply\ForumReplyViewBuilder",
 *     "list_builder" = "Drupal\thunder_forum_reply\ForumReplyListBuilder",
 *     "views_data" = "Drupal\thunder_forum_reply\ForumReplyViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\thunder_forum_reply\Form\ForumReplyForm",
 *       "edit" = "Drupal\thunder_forum_reply\Form\ForumReplyForm",
 *       "delete" = "Drupal\thunder_forum_reply\Form\ForumReplyDeleteForm",
 *     },
 *     "access" = "Drupal\thunder_forum_reply\ForumReplyAccessControlHandler",
 *   },
 *   base_table = "thunder_forum_reply",
 *   data_table = "thunder_forum_reply_field_data",
 *   revision_table = "thunder_forum_reply_revision",
 *   revision_data_table = "thunder_forum_reply_field_revision",
 *   uri_callback = "thunder_forum_reply_uri",
 *   admin_permission = "administer forum replies",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "frid",
 *     "revision" = "vid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *     "topic" = "nid",
 *     "parent" = "pfrid",
 *   },
 *   links = {
 *     "canonical" = "/forum/reply/{thunder_forum_reply}",
 *     "edit-form" = "/forum/reply/{thunder_forum_reply}/edit",
 *     "delete-form" = "/forum/reply/{thunder_forum_reply}/delete",
 *     "version-history" = "/forum/reply/{thunder_forum_reply}/revisions",
 *     "revision" = "/forum/reply/{thunder_forum_reply}/revisions/{thunder_forum_reply_revision}/view",
 *     "revision-revert" = "/forum/reply/{thunder_forum_reply}/revisions/{thunder_forum_reply_revision}/revert",
 *     "translation-revert" = "/forum/reply/{default_entity}/revisions/{thunder_forum_reply_revision}/revert/{langcode}",
 *     "revision-delete" = "/forum/reply/{thunder_forum_reply}/revisions/{thunder_forum_reply_revision}/delete",
 *     "collection" = "/admin/content/forum/reply"
 *   },
 *   field_ui_base_route = "entity.thunder_forum_reply.collection",
 * )
 */
class ForumReply extends ContentEntityBase implements ForumReplyInterface {

  use StringTranslationTrait;
  
  /**
   * Whether the forum reply is being previewed or not.
   *
   * @var true|null
   *   TRUE if the forum reply is being previewed and NULL if it is not.
   */
  public $in_preview = NULL;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'create') {
      // Add context to 'create' access checks.
      return $this->entityTypeManager()
        ->getAccessControlHandler($this->entityTypeId)
        ->createAccess($this->bundle(), $account, [
          'field_name' => $this->getFieldName(),
          'nid' => $this->getRepliedNodeId(),
          'pfrid' => $this->getParentReplyId(),
        ], $return_as_object);
    }

    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    // User ID.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the forum reply author.'))
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\thunder_forum_reply\Entity\ForumReply::getCurrentUserId');

    // Topic ID.
    $fields['nid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Topic ID'))
      ->setDescription(t('The node ID of the forum topic the reply belongs to.'))
      ->setSetting('target_type', 'node')
      ->setSetting('target_bundles', ['forum'])
      ->setRequired(TRUE);

    // Parent forum reply ID.
    $fields['pfrid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent forum reply ID'))
      ->setDescription(t('The ID of the forum reply this item is a direct reply to.'))
      ->setSetting('target_type', 'thunder_forum_reply');

    // Title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Body.
    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Body'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Status.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the forum reply is published.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 15,
      ]);

    // Created date.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Changed date.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hostname'))
      ->setDescription(t("The forum reply author's hostname."))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 128);

    // Revision timestamp.
    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    // Revision user ID.
    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision user ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    // Revision log message.
    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision log message'))
      ->setDescription(t('Briefly describe the changes you have made.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(NULL);

    // Revision translation affected.
    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    // Forum reply field name.
    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Forum reply field name'))
      ->setDescription(t('The field name through which this forum reply was added.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTimeAcrossTranslations() {
    $changed = $this->getUntranslated()->getChangedTime();

    foreach ($this->getTranslationLanguages(FALSE) as $language) {
      $translation_changed = $this->getTranslation($language->getId())->getChangedTime();
      $changed = max($translation_changed, $changed);
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->get('field_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostname() {
    return $this->get('hostname')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentReply() {
    return $this->get('pfrid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentReplyId() {
    return $this->get('pfrid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepliedNode() {
    return $this->get('nid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepliedNodeId() {
    return $this->get('nid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionAuthor() {
    return $this->getRevisionUser();
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionCreationTime() {
    return $this->get('revision_timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLogMessage() {
    return $this->get('revision_log')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUser() {
    return $this->get('revision_uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionUserId() {
    return $this->get('revision_uid')->entity->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasParentReply() {
    return (bool) $this->get('pfrid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function permalink() {
    $uri = $this->urlInfo();
    $uri->setOption('fragment', 'forum-reply-' . $this->id());

    return $uri;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var \Drupal\thunder_forum_reply\ForumReplyStatisticsInterface $statistics */
    $statistics = \Drupal::service('thunder_forum_reply.statistics');

    foreach ($entities as $id => $entity) {
      $statistics->update($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Always invalidate the cache tag for the replied node entity.
    if ($replied_node = $this->getRepliedNode()) {
      Cache::invalidateTags($replied_node->getCacheTagsToInvalidate());
    }

    /** @var \Drupal\thunder_forum_reply\ForumReplyStatisticsInterface $statistics */
    $statistics = \Drupal::service('thunder_forum_reply.statistics');

    // Update the {thunder_forum_reply_node_statistics} table prior to executing
    // the hook.
    $statistics->update($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);

    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\thunder_forum_reply\ForumReplyInterface[] $entities */
    parent::preDelete($storage, $entities);

    // Build list of parent forum reply IDs.
    $pfrids = [];
    foreach ($entities as $entity) {
      $pfrids[] = $entity->id();
    }

    if ($pfrids) {
      $child_frids = $storage->getQuery()
        ->condition('pfrid', $pfrids, 'IN')
        ->execute();

      /** @var \Drupal\thunder_forum_reply\ForumReplyInterface[] $children */
      $children = $storage->loadMultiple($child_frids);

      // Reset parent forum reply IDs for children.
      foreach ($children as $child) {
        $child->set('pfrid', NULL);
        $child->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Ensure current host name (IP).
    if (!$this->getHostname()) {
      $this->setHostname(\Drupal::request()->getClientIP());
    }

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the forum reply owner
    // the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }

    // Validate the forum reply's subject. If not specified, extract from forum
    // reply body.
    if (trim($this->getSubject()) == '') {
      if ($this->hasField('body')) {
        // The body may be in any format, so:
        // 1) Filter it into HTML
        // 2) Strip out all HTML tags
        // 3) Convert entities back to plain-text.
        $body = $this->body->processed;
        $this->setSubject(Unicode::truncate(trim(Html::decodeEntities(strip_tags($body))), 29, TRUE, TRUE));
      }

      // Edge cases where the forum reply body is populated only by HTML tags
      // will require a default subject.
      if ($this->getSubject() == '') {
        $this->setSubject($this->t('(No subject)'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing forum reply without adding a new
      // revision, we need to make sure $entity->revision_log is reset whenever
      // it is empty. Therefore, this code allows us to avoid clobbering an
      // existing log entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldName($field_name) {
    $this->set('field_name', $field_name);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostname($hostname) {
    $this->set('hostname', $hostname);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? static::PUBLISHED : static::NOT_PUBLISHED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionAuthorId($uid) {
    $this->setRevisionUserId($uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionCreationTime($timestamp) {
    $this->set('revision_timestamp', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLogMessage($revision_log_message) {
    $this->set('revision_log', $revision_log_message);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUser(UserInterface $user) {
    $this->set('revision_uid', $user);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionUserId($user_id) {
    $this->set('revision_uid', $user_id);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('title', $subject);

    return $this;
  }

}
