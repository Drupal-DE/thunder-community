<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlagLinkBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for flags provided by "Thunder Private Message".
 */
class FlagSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagLinkBuilder
   */
  protected $linkBuilder;

  /**
   * Constructs a new FlagSubscriber.
   *
   * @param \Drupal\flag\FlagLinkBuilder $link_builder
   *   Flag link builder to use.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user account.
   */
  public function __construct(FlagLinkBuilder $link_builder, AccountInterface $current_user) {
    $this->linkBuilder = $link_builder;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    $events[FlagEvents::ENTITY_FLAGGED][] = ['messageUndo', -100];

    return $events;
  }

  /**
   * Display a flag message with possibility to undo (unflag).
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function messageUndo(FlaggingEvent $event) {
    /* @var $flagging \Drupal\flag\FlaggingInterface */
    $flagging = $event->getFlagging();

    /* @var $flag \Drupal\flag\FlagInterface */
    $flag = $flagging->getFlag();

    /** @var \Drupal\message\MessageInterface $entity */
    $entity = $flagging->getFlaggable();

    if ($flag->isFlagged($entity, $this->currentUser)) {
      // @todo Does not work currently due to wrong CSRF token which blocks
      // access to unflag route.
      drupal_set_message($this->t('@message @undo', [
        '@message' => $flag->getFlagMessage(),
        '@undo' => $flag->getLinkTypePlugin()->getAsLink($flag, $entity)->toString(),
      ]), 'status', TRUE);
    }
  }

}
