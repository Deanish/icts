<?php

namespace Drupal\Tests\ds\Functional;

/**
 * Tests user functionality
 *
 * @group ds
 */
class UserTest extends TestBase {

  /**
   * Tests hook_preprocess_layout() with user entities
   */
  public function testCompatibility() {

    // Create a test user.
    /** @var \Drupal\user\UserInterface $new_user */
    $new_user = $this->drupalCreateUser([
      'access content',
    ]);

    $this->dsSelectLayout([], [],'admin/config/people/accounts/display');

    $fields = [
      'fields[username][region]' => 'right',
    ];

    $this->dsConfigureUi($fields,'admin/config/people/accounts/display');

    $this->drupalGet('user/' . $new_user->id());

    $this->assertSession()->responseContains('entity-label-class-' . $new_user->label());
  }

}
