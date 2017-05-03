<?php

namespace Drupal\Tests\thunder_forum_access\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\BrowserTestBase;
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
   * List of user accounts.
   *
   * @var \Drupal\Core\Session\AccountInterface[];
   */
  protected $accounts = [];

  /**
   * Associative list of forum terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'thunder';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'taxonomy',
    'forum',
    'thunder_ach',
    'thunder_taxonomy',
    'thunder_forum',
    'thunder_forum_access',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create moderator account.
    $this->accounts['moderator'] = $this->createUser([
      'access content',
      'view published terms in forums',
      'view unpublished terms in forums',
    ]);
    $this->accounts['member'] = $this->createUser([
      'access content',
      'view published terms in forums',
    ]);
    $this->accounts['default'] = $this->createUser([
      'access content',
      'view published terms in forums',
    ]);

    $vocabulary = Vocabulary::load('forums');

    $this->terms['container'] = $this->createTerm($vocabulary, [
      'name' => 'Forum test: container',
      // Published and visible to all users.
      'status' => 1,
      'forum_container' => 1,
    ]);
    $this->terms['public'] = $this->createTerm($vocabulary, [
      'name' => 'Forum test: public forum',
      // Published and visible to all users.
      'status' => 1,
      'forum_container' => 0,
    ]);
    $this->terms['private'] = $this->createTerm($vocabulary, [
      'name' => 'Forum test: public forum',
      // Published.
      'status' => 1,
      'forum_container' => 0,
      // Limited to a list of selected users.
      'field_forum_is_private' => 1,
      'field_forum_moderators' => [
        $this->accounts['moderator']->id(),
      ],
      'field_forum_members' => [
        $this->accounts['member']->id(),
      ],
    ]);
  }

  /**
   * Test visibility of public form.
   */
  public function testPublicForumVisibility() {
    $this->drupalLogin($this->accounts['default']);
    $this->drupalGet('forum/' . $this->terms['public']->id());
    // A user with no special permissions is allowed to access this forum.
    $this->assertResponse(200);
  }

  /**
   * Test visibility of public form.
   */
  public function testPrivateForumVisibility() {
    // Login as default user.
    $this->drupalLogin($this->accounts['default']);
    $this->drupalGet('forum/' . $this->terms['private']->id());
    // A user with no special permissions is not allowed to access this forum.
    $this->assertResponse(403);
    // Login as moderator of private forum.
    $this->drupalLogin($this->accounts['moderator']);
    $this->drupalGet('forum/' . $this->terms['private']->id());
    // The user is allowed to access this forum.
    $this->assertResponse(200);
    // Login as member of private forum.
    $this->drupalLogin($this->accounts['member']);
    $this->drupalGet('forum/' . $this->terms['private']->id());
    // The user is allowed to access this forum.
    $this->assertResponse(200);
  }

  /**
   * Helper function to initialize the form display of vocabulary "forums".
   *
   * @todo Make this work and integrate in setUp().
   */
  private function initFormDisplay() {
    if (($display = EntityFormDisplay::load('taxonomy_term.forums.default')) === NULL) {
      return;
    }

    $entity_reference_default = [
      'settings' => [
        'match_operator' => 'CONTAINS',
        'placeholder' => '',
        'size' => 60,
      ],
      'third_party_settings' => [],
      'type' => 'entity_reference_autocomplete',
    ];
    $checkbox_default = [
      'settings' => [
        'display_label' => TRUE,
      ],
      'third_party_settings' => [],
      'type' => 'boolean_checkbox',
    ];

    $components = [
      'field_forum_moderators' => $entity_reference_default + [
        'weight' => 1,
      ],
      'field_forum_is_locked' => $checkbox_default + [
        'weight' => 2,
      ],
      'field_forum_members' => $entity_reference_default + [
        'weight' => 4,
      ],
      'field_forum_is_private' => $checkbox_default + [
        'weight' => 3,
      ],
    ];

    $save = FALSE;
    foreach ($components as $name => $component) {
      if ($display->getComponent($name) !== NULL) {
        // Field is already configured.
        continue;
      }
      $display->setComponent($name, $component);
      $save = TRUE;
    }
    if ($save) {
      $display->save();
    }
  }

}
