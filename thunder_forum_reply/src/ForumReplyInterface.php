<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a forum reply entity.
 */
interface ForumReplyInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface {

  /**
   * Denotes that the forum reply is not published.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Denotes that the forum reply is published.
   */
  const PUBLISHED = 1;

  /**
   * Returns the default subject of for a forum reply.
   *
   * In the form 'RE: Parent item title'.
   *
   * @return string
   *   The default subject.
   */
  public function getDefaultSubject();

  /**
   * Returns the name of the field the forum reply is attached to.
   *
   * @return string
   *   The name of the field the forum reply is attached to.
   */
  public function getFieldName();

  /**
   * Returns the forum reply author's hostname.
   *
   * @return string
   *   The hostname of the author of the forum reply.
   */
  public function getHostname();

  /**
   * Return quote text of the parent.
   *
   * @return string
   *   The quotable text of the parent.
   */
  public function getParentQuoteText();

  /**
   * Returns parent forum reply if a response to another forum reply.
   *
   * @return \Drupal\thunder_forum_reply\ForumReplyInterface|null
   *   A forum reply entity of the parent forum reply or NULL if there is no
   *   parent.
   */
  public function getParentReply();

  /**
   * Returns ID of the parent forum reply if a response to another forum reply.
   *
   * @return int|null
   *   A forum reply entity ID of the parent forum reply or NULL if there is no
   *   parent.
   */
  public function getParentReplyId();

  /**
   * Returns the forum topic node to which the forum reply is attached.
   *
   * @return \Drupal\node\NodeInterface
   *   The forum topic node on which the forum reply is attached.
   */
  public function getRepliedNode();

  /**
   * Returns ID of the forum topic node to which the forum reply is attached.
   *
   * @return int
   *   The ID of the forum topic node to which the forum reply is attached.
   */
  public function getRepliedNodeId();

  /**
   * Whether forum reply message should contain a quote of the parent on create.
   *
   * @return bool
   *   Whether the forum reply message should contain a quote of the parent on
   *   creation.
   */
  public function getShouldContainParentQuoteOnCreate();

  /**
   * Returns the subject of the forum reply.
   *
   * @return string
   *   The subject of the forum reply.
   */
  public function getSubject();

  /**
   * Determines if this forum reply is a response to another forum reply.
   *
   * @return bool
   *   TRUE if the forum reply has a parent reply otherwise FALSE.
   */
  public function hasParentReply();

  /**
   * Returns the forum reply published status indicator.
   *
   * @return bool
   *   TRUE if the forum reply is published.
   */
  public function isPublished();

  /**
   * Returns the permalink URL for this forum reply.
   *
   * @return \Drupal\Core\Url
   *   The permalink URL object.
   */
  public function permalink();

  /**
   * Sets the field ID for which this forum reply is attached.
   *
   * @param string $field_name
   *   The field name through which the forum reply was added.
   *
   * @return static
   *   The class instance that this method is called on.
   */
  public function setFieldName($field_name);

  /**
   * Sets the hostname of the author of the forum reply.
   *
   * @param string $hostname
   *   The hostname of the author of the forum reply.
   *
   * @return static
   *   The class instance that this method is called on.
   */
  public function setHostname($hostname);

  /**
   * Sets the published status of a forum reply.
   *
   * @param bool $published
   *   TRUE to set this forum reply to published, FALSE to set it to
   *   unpublished.
   *
   * @return static
   *   The class instance that this method is called on.
   */
  public function setPublished($published);

  /**
   * Sets whether the forum reply message should contain a quote of the parent.
   *
   * @param bool $quote
   *   Whether the forum reply message should contain a quote of the parent on
   *   creation.
   *
   * @return static
   *   The class instance that this method is called on.
   */
  public function setShouldContainParentQuoteOnCreate($quote);

  /**
   * Sets the subject of the forum reply.
   *
   * @param string $subject
   *   The subject of the forum reply.
   *
   * @return static
   *   The class instance that this method is called on.
   */
  public function setSubject($subject);

}
