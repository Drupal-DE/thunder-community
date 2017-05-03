<?php

namespace Drupal\Tests\thunder_forum_subscription\Functional;

use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Test forum subscriptions.
 *
 * @group thunder_forum_subscription
 */
class ForumSubscriptionTest extends BrowserTestBase {

  /**
   * Associative list of forum terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'flag',
    'forum',
    'node',
    'taxonomy',
    'thunder_forum',
    'thunder_forum_subscription',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create forum container.
    $this->terms['container'] = Term::create([
      'forum_container' => 1,
      'name' => 'Container',
      'vid' => 'forums',
    ]);

    // Create forum.
    $this->terms['forum'] = Term::create([
      'forum_container' => 0,
      'name' => 'Forum',
      'parent' => $this->terms['container']->id(),
      'vid' => 'forums'
    ]);

    // @todo Create topic.
  }

  /**
   * @todo Tests forum container subscription access.
   */
  public function testForumContainerSubscriptionAccess() {
    $this->drupalGet(Url::fromRoute('entity.taxonomy_term.canonical', [
      'taxonomy_term' => $this->terms['container']->id(),
    ]));
  }

  /**
   * @todo Tests forum subscription access.
   */
  public function testForumForumSubscriptionAccess() {
    $this->drupalGet(Url::fromRoute('entity.taxonomy_term.canonical', [
      'taxonomy_term' => $this->terms['forum']->id(),
    ]));
  }

  /**
   * @todo Tests forum topic subscription access.
   */
  public function testForumTopicSubscriptionAccess() {
    // $this->drupalGet(Url::fromRoute('entity.node.canonical', [
    //   'node' => $this->nodes['topic']->id(),
    // ]));
  }

}
