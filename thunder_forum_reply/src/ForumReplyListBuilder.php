<?php

namespace Drupal\thunder_forum_reply;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of forum reply entities.
 *
 * @see \Drupal\thunder_forum_reply\Entity\ForumReply
 */
class ForumReplyListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new ForumReplyListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\thunder_forum_reply\ForumReplyStorageInterface $storage
   *   The forum reply entity storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, ForumReplyStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'created' => [
        'data' => $this->t('Date'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'status' => $this->t('Status'),
      'title' => $this->t('Title'),
      'posted_on' => [
        'data' => $this->t('Posted on'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'author' => [
        'data' => $this->t('Author'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\thunder_forum_reply\ForumReplyInterface */
    $row = [];

    // Created date.
    $row['created'] = [
      'data' => $this->dateFormatter->format($entity->getCreatedTime(), 'short'),
    ];

    // Status.
    $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');

    // Title.
    $row['title'] = [
      'data' => $entity->toLink(),
    ];

    // Replied forum topic node.
    $row['posted_on'] = [
      'data' => $entity->getRepliedNode()->toLink(),
    ];

    // Author.
    $row['author'] = [
      'data' => [
        '#theme' => 'username',
        '#account' => $entity->getOwner(),
      ],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
