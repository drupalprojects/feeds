<?php

/**
 * @file
 * Contains \Drupal\feeds\Tests\Row\RowTest.
 */

namespace Drupal\feeds\Tests\Row;

use Drupal\feeds\Row\Row;
use Drupal\feeds\Table\Table;
use Drupal\feeds\Tests\FeedsUnitTestCase;

/**
 * @covers \Drupal\feeds\Row\Row
 */
class RowTest extends FeedsUnitTestCase {

  protected function getMockTable($field, $value) {
    $table = $this->getMock('Drupal\feeds\Table\TableInterface');

    $table->expects($this->once())
      ->method('get')
      ->with($this->equalTo($field))
      ->will($this->returnValue($value));

    return $table;
  }

  public function testRow() {
    $row = new Row($this->getMockTable('does not exist', 9876));

    $this->assertSame(9876, $row->get('does not exist'));

    // Test get, set and fluidity at once.
    $this->assertSame(1234, $row->set('exists', 1234)->get('exists'));

    $this->assertSame(array('beep', 'boop'), $row->setData(array('beep', 'boop'))->getData());
  }

}
