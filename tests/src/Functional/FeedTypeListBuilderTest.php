<?php

namespace Drupal\Tests\feeds\Functional;

/**
 * Tests the feed type list page.
 *
 * @group feeds
 */
class FeedTypeListBuilderTest extends FeedsBrowserTestBase {

  /**
   * Tests the display of feed types.
   */
  public function testUi() {
    $this->drupalGet('/admin/structure/feeds');
    // Assert that a message is displayed that there is no feed type yet.
    $this->assertSession()->pageTextContains('There is no Feed type yet.');

    // Now add a feed type.
    $this->feedType = $this->createFeedType([
      'id' => 'my_feed_type',
      'label' => 'My feed type',
    ]);

    // Assert feed type name and operation links being displayed.
    $this->drupalGet('/admin/structure/feeds');
    $session = $this->assertSession();

    $session->pageTextContains('My feed type');
    $session->linkExists('Edit');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type');
    $session->linkExists('Mapping');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/mapping');
    $session->linkExists('Delete');
    $session->linkByHrefExists('/admin/structure/feeds/manage/my_feed_type/delete');
  }

}
