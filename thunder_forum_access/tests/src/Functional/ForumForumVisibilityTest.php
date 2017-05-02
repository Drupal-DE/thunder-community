<?php

namespace Drupal\Tests\thunder_forum_access\Functional;

use Drupal\simpletest\BrowserTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Tests\TaxonomyTestTrait;

/**
 * Test visibility of forum structure.
 *
 * @group thunder_forum_access
 */
class ForumForumVisibilityTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * A moderator account.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $moderator;

  /**
   * A simple user account.
   *
   * @var \Drupal\Core\Session\AccountInterface;
   */
  protected $account;

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
  public static $modules = ['taxonomy', 'forum', 'thunder_forum_access'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $vocabulary = Vocabulary::load('forums');

    $this->terms['container'] = $this->createTerm($vocabulary, [
      'name' => 'Forum test container',
      // Published and visible to all users.
      'status' => 1,
    ]);
  }

  /**
   * Blah.
   */
  public function testForumVisibility() {
  }

}
