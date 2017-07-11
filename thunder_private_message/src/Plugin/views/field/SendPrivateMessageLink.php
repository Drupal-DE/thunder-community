<?php

namespace Drupal\thunder_private_message\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\thunder_private_message\PrivateMessageHelperInterface;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to provide a simple link to send a private message to a user.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("thunder_private_message_send_link")
 */
class SendPrivateMessageLink extends LinkBase {

  /**
   * The private message helper.
   *
   * @var \Drupal\thunder_private_message\PrivateMessageHelperInterface
   */
  protected $privateMessageHelper;

  /**
   * Constructs a SendPrivateMessageLink object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\thunder_private_message\PrivateMessageHelperInterface $private_message_helper
   *   The private message helper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessManagerInterface $access_manager, PrivateMessageHelperInterface $private_message_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $access_manager);

    $this->privateMessageHelper = $private_message_helper;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowAdvancedRender() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This method may be removed completely, when
   * https://www.drupal.org/node/2886800 is fixed.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('thunder_private_message.helper')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo This method may be removed, when https://www.drupal.org/node/2886800
   * is fixed. Also clean up dependency injection stuff, constructor, properties
   * etc.
   */
  protected function checkUrlAccess(ResultRow $row) {
    $access = parent::checkUrlAccess($row);

    // We have to do this check again here, because the
    // \Drupal\Core\Entity\EntityAccessControlHandler::createAccess() method
    // statically caches its results, but does not take different context values
    // into account (see linked issue above).
    $access = $access->orIf(AccessResult::forbiddenIf(!$this->privateMessageHelper->userCanWriteMessageToOtherUser($this->getEntity($row))));

    return $access;
  }

  /**
   * Returns the default label for this link.
   *
   * @return string
   *   The default link label.
   */
  protected function getDefaultLabel() {
    return $this->t('Send private message');
  }

  /**
   * Returns the URI elements of the link.
   *
   * @param \Drupal\views\ResultRow $row
   *   A view result row.
   *
   * @return \Drupal\Core\Url
   *   The URI elements of the link.
   */
  protected function getUrlInfo(ResultRow $row) {
    return Url::fromRoute('thunder_private_message.add', [
      'user' => $this->currentUser()->id(),
      'recipient' => $this->getEntity($row)->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    // Add destination parameter.
    $this->options['alter']['query'] = $this->getDestinationArray();

    $text = parent::renderLink($row);

    /** @var \Drupal\Core\Url $url */
    $url = $this->options['alter']['url'];
    $url->setOption('query', $this->options['alter']['query']);

    $build = [
      '#theme' => 'thunder_private_message_link_send__views_view_field__' . $this->view->id() . '__' . $this->view->current_display . '__' . $this->getPluginId(),
      '#link' => Link::fromTextAndUrl($text, $url)->toRenderable(),
      '#uid_recipient' => $this->getEntity($row)->id(),
      '#uid_sender' => $this->currentUser()->id(),
    ];

    return $this->getRenderer()->render($build);
  }

}
