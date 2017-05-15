<?php

namespace Drupal\thunder_forum\Plugin\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route parameters needed to link to the current user tracker tab.
 */
class EditForumTermTab extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct the UnapprovedComments object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $route_match, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = Cache::mergeContexts(['url.path', 'user'], parent::getCacheContexts());

    return Cache::mergeContexts($contexts, $this->getTerm()->getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::mergeMaxAges(parent::getCacheMaxAge(), $this->getTerm()->getCacheMaxAge());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), $this->getTerm()->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);

    // Add 'destination' query string parameter.
    $options['query'] = array_merge(isset($options['query']) ? $options['query'] : [], $this->redirectDestination->getAsArray());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    if ($this->isForumContainer()) {
      return 'entity.taxonomy_term.forum_edit_container_form';
    }

    return parent::getRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(RouteMatchInterface $route_match) {
    return ['taxonomy_term' => $this->getTerm()->id()];
  }

  /**
   * Return forum taxonomy term route parameter value.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The forum taxonomy term.
   */
  protected function getTerm() {
    return $this->routeMatch->getParameter('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return $this->isForumContainer() ? $this->t('Edit container') : parent::getTitle($request);
  }

  /**
   * Is forum container.
   *
   * @return bool
   *   Whether the current forum taxonomy term route parameter reflects a forum
   *   container.
   */
  protected function isForumContainer() {
    return !empty($this->getTerm()->forum_container->value);
  }

}
