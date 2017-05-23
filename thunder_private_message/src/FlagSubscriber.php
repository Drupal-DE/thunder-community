<?php

namespace Drupal\thunder_private_message;

use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\FlagLinkBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function drupal_set_message;

/**
 * Event subscriber for flags provided by "Thunder Private Message".
 */
class FlagSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagLinkBuilder
   */
  protected $linkBuilder;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new FlagSubscriber.
   *
   * @param \Drupal\flag\FlagLinkBuilder $link_builder
   *   Flag link builder to use.
   */
  public function __construct(FlagLinkBuilder $link_builder, AccountInterface $account) {
    $this->linkBuilder = $link_builder;
    $this->account = $account;
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
    $entity = $flagging->getFlaggable();

    // Unfortunately we have to build the ink by ourself.
    $destination = new Url('view.private_messages.inbox', ['user' => $this->account->id()]);
    $action = $flag->isFlagged($entity) ? 'unflag' : 'flag';
    $url = $flag->getLinkTypePlugin()->getUrl($action, $flag, $entity);
    $url->setOption('query', ['destination' => $destination->getInternalPath()]);
    $title = $action === 'unflag' ? $flag->getUnflagShortText() : $flag->getFlagShortText();

    drupal_set_message($this->t('@message @undo', ['@message' => $flag->getFlagMessage(), '@undo' => Link::fromTextAndUrl($title, $url)->toString()]));
  }

}
