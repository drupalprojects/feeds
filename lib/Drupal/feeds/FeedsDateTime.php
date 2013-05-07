<?php

namespace Drupal\feeds;

use DateTime;

/**
 * Extend PHP DateTime class with granularity handling, merge functionality and
 * slightly more flexible initialization parameters.
 *
 * This class is a Drupal independent extension of the >= PHP 5.2 DateTime
 * class.
 *
 * @see FeedsDateTimeElement
 */
class FeedsDateTime extends DateTime {
  public $granularity = array();
  protected static $allgranularity = array('year', 'month', 'day', 'hour', 'minute', 'second', 'zone');
  private $_serialized_time;
  private $_serialized_timezone;

  /**
   * Helper function to prepare the object during serialization.
   *
   * We are extending a core class and core classes cannot be serialized.
   *
   * Ref: http://bugs.php.net/41334, http://bugs.php.net/39821
   */
  public function __sleep() {
    $this->_serialized_time = $this->format('c');
    $this->_serialized_timezone = $this->getTimezone()->getName();
    return array('_serialized_time', '_serialized_timezone');
  }

  /**
   * Upon unserializing, we must re-build ourselves using local variables.
   */
  public function __wakeup() {
    $this->__construct($this->_serialized_time, new \DateTimeZone($this->_serialized_timezone));
  }

  /**
   * Overridden constructor.
   *
   * @param $time
   *   time string, flexible format including timestamp. Invalid formats will
   *   fall back to 'now'.
   * @param $tz
   *   PHP \DateTimeZone object, NULL allowed
   */
  public function __construct($time = '', $tz = NULL) {
    // Assume UNIX timestamp if numeric.
    if (is_numeric($time)) {
      // Make sure it's not a simple year
      if ((is_string($time) && strlen($time) > 4) || is_int($time)) {
        $time = "@" . $time;
      }
    }

    // PHP < 5.3 doesn't like the GMT- notation for parsing timezones.
    $time = str_replace("GMT-", "-", $time);
    $time = str_replace("GMT+", "+", $time);

    // Some PHP 5.2 version's DateTime class chokes on invalid dates.
    if (!strtotime($time)) {
      $time = 'now';
    }

    // Create and set time zone separately, PHP 5.2.6 does not respect time zone
    // argument in __construct().
    parent::__construct($time);
    $tz = $tz ? $tz : new \DateTimeZone("UTC");
    $this->setTimeZone($tz);

    // Verify that timezone has not been specified as an offset.
    if (!preg_match('/[a-zA-Z]/', $this->getTimezone()->getName())) {
      $this->setTimezone(new \DateTimeZone("UTC"));
    }

    // Finally set granularity.
    $this->setGranularityFromTime($time, $tz);
  }

  /**
   * This function will keep this object's values by default.
   */
  public function merge(FeedsDateTime $other) {
    $other_tz = $other->getTimezone();
    $this_tz = $this->getTimezone();
    // Figure out which timezone to use for combination.
    $use_tz = ($this->hasGranularity('zone') || !$other->hasGranularity('zone')) ? $this_tz : $other_tz;

    $this2 = clone $this;
    $this2->setTimezone($use_tz);
    $other->setTimezone($use_tz);
    $val = $this2->toArray();
    $otherval = $other->toArray();
    foreach (self::$allgranularity as $g) {
      if ($other->hasGranularity($g) && !$this2->hasGranularity($g)) {
        // The other class has a property we don't; steal it.
        $this2->addGranularity($g);
        $val[$g] = $otherval[$g];
      }
    }
    $other->setTimezone($other_tz);

    $this2->setDate($val['year'], $val['month'], $val['day']);
    $this2->setTime($val['hour'], $val['minute'], $val['second']);
    return $this2;
  }

  /**
   * Overrides default DateTime function. Only changes output values if
   * actually had time granularity. This should be used as a "converter" for
   * output, to switch tzs.
   *
   * In order to set a timezone for a datetime that doesn't have such
   * granularity, merge() it with one that does.
   */
  public function setTimezone($tz, $force = FALSE) {
    // PHP 5.2.6 has a fatal error when setting a date's timezone to itself.
    // http://bugs.php.net/bug.php?id=45038
    if (version_compare(PHP_VERSION, '5.2.7', '<') && $tz == $this->getTimezone()) {
      $tz = new \DateTimeZone($tz->getName());
    }

    if (!$this->hasTime() || !$this->hasGranularity('zone') || $force) {
      // this has no time or timezone granularity, so timezone doesn't mean much
      // We set the timezone using the method, which will change the day/hour, but then we switch back
      $arr = $this->toArray();
      parent::setTimezone($tz);
      $this->setDate($arr['year'], $arr['month'], $arr['day']);
      $this->setTime($arr['hour'], $arr['minute'], $arr['second']);
      return;
    }
    parent::setTimezone($tz);
  }

  /**
   * Safely adds a granularity entry to the array.
   */
  public function addGranularity($g) {
    $this->granularity[] = $g;
    $this->granularity = array_unique($this->granularity);
  }

  /**
   * Removes a granularity entry from the array.
   */
  public function removeGranularity($g) {
    if ($key = array_search($g, $this->granularity)) {
      unset($this->granularity[$key]);
    }
  }

  /**
   * Checks granularity array for a given entry.
   */
  public function hasGranularity($g) {
    return in_array($g, $this->granularity);
  }

  /**
   * Returns whether this object has time set. Used primarily for timezone
   * conversion and fomratting.
   *
   * @todo currently very simplistic, but effective, see usage
   */
  public function hasTime() {
    return $this->hasGranularity('hour');
  }

  /**
   * Protected function to find the granularity given by the arguments to the
   * constructor.
   */
  protected function setGranularityFromTime($time, $tz) {
    $this->granularity = array();
    $temp = date_parse($time);
    // This PHP method currently doesn't have resolution down to seconds, so if
    // there is some time, all will be set.
    foreach (self::$allgranularity AS $g) {
      if ((isset($temp[$g]) && is_numeric($temp[$g])) || ($g == 'zone' && (isset($temp['zone_type']) && $temp['zone_type'] > 0))) {
        $this->granularity[] = $g;
      }
    }
    if ($tz) {
      $this->addGranularity('zone');
    }
  }

  /**
   * Helper to return all standard date parts in an array.
   */
  protected function toArray() {
    return array('year' => $this->format('Y'), 'month' => $this->format('m'), 'day' => $this->format('d'), 'hour' => $this->format('H'), 'minute' => $this->format('i'), 'second' => $this->format('s'), 'zone' => $this->format('e'));
  }
}
