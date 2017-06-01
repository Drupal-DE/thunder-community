<?php

namespace Drupal\thunder_forum_reply\Plugin\Field\FieldType;

/**
 * Interface definition for forum reply items.
 */
interface ForumReplyItemInterface {

  /**
   * Forum replies for this entity are hidden.
   */
  const HIDDEN = 0;

  /**
   * Forum replies for this entity are not allowed to be created.
   */
  const CLOSED = 1;

  /**
   * Forum replies for this entity are allowed.
   */
  const OPEN = 2;

  /**
   * Forum reply form should be displayed on a separate page.
   */
  const FORM_SEPARATE_PAGE = 0;

  /**
   * Forum reply form should be shown below post or list of forum replies.
   */
  const FORM_BELOW = 1;

}
