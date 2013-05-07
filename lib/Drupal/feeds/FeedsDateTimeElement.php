<?php

namespace Drupal\feeds;
use DateTimeZone;

/**
 * Defines a date element of a parsed result (including ranges, repeat).
 */
class FeedsDateTimeElement extends FeedsElement {

  // Start date and end date.
  public $start;
  public $end;

  /**
   * Constructor.
   *
   * @param $start
   *   A FeedsDateTime object or a date as accepted by FeedsDateTime.
   * @param $end
   *   A FeedsDateTime object or a date as accepted by FeedsDateTime.
   * @param $tz
   *   A PHP DateTimeZone object.
   */
  public function __construct($start = NULL, $end = NULL, $tz = NULL) {
    $this->start = (!isset($start) || ($start instanceof FeedsDateTime)) ? $start : new FeedsDateTime($start, $tz);
    $this->end = (!isset($end) || ($end instanceof FeedsDateTime)) ? $end : new FeedsDateTime($end, $tz);
  }

  /**
   * Override FeedsElement::getValue().
   *
   * @return
   *   The UNIX timestamp of this object's start date. Return value is
   *   technically a string but will only contain numeric values.
   */
  public function getValue() {
    if ($this->start) {
      return $this->start->format('U');
    }
    return '0';
  }

  /**
   * Merge this field with another. Most stuff goes down when merging the two
   * sub-dates.
   *
   * @see FeedsDateTime
   */
  public function merge(FeedsDateTimeElement $other) {
    $this2 = clone $this;
    if ($this->start && $other->start) {
      $this2->start = $this->start->merge($other->start);
    }
    elseif ($other->start) {
      $this2->start = clone $other->start;
    }
    elseif ($this->start) {
      $this2->start = clone $this->start;
    }

    if ($this->end && $other->end) {
      $this2->end = $this->end->merge($other->end);
    }
    elseif ($other->end) {
      $this2->end = clone $other->end;
    }
    elseif ($this->end) {
      $this2->end = clone $this->end;
    }
    return $this2;
  }

  /**
   * Helper method for buildDateField(). Build a FeedsDateTimeElement object
   * from a standard formatted node.
   */
  protected static function readDateField($entity, $field_name, $delta = 0) {
    $ret = new FeedsDateTimeElement();
    if (isset($entity->{$field_name}['und'][$delta]['date']) && $entity->{$field_name}['und'][$delta]['date'] instanceof FeedsDateTime) {
      $ret->start = $entity->{$field_name}['und'][$delta]['date'];
    }
    if (isset($entity->{$field_name}['und'][$delta]['date2']) && $entity->{$field_name}['und'][$delta]['date2'] instanceof FeedsDateTime) {
      $ret->end = $entity->{$field_name}['und'][$delta]['date2'];
    }
    return $ret;
  }

  /**
   * Build a entity's date field from our object.
   *
   * @param object $entity
   *   The entity to build the date field on.
   * @param str $field_name
   *   The name of the field to build.
   * @param int $delta
   *   The delta in the field.
   */
  public function buildDateField($entity, $field_name, $delta = 0) {
    $info = field_info_field($field_name);

    $oldfield = FeedsDateTimeElement::readDateField($entity, $field_name, $delta);
    // Merge with any preexisting objects on the field; we take precedence.
    $oldfield = $this->merge($oldfield);
    $use_start = $oldfield->start;
    $use_end = $oldfield->end;

    // Set timezone if not already in the FeedsDateTime object
    $temp = new FeedsDateTime(NULL, new DateTimeZone(DATETIME_STORAGE_TIMEZONE));

    if ($use_start) {
      $use_start = $use_start->merge($temp);
      $use_start->setTimezone(new DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    }
    if ($use_end) {
      $use_end = $use_end->merge($temp);
      $use_end->setTimezone(new DateTimeZone(DATETIME_STORAGE_TIMEZONE));
    }

    $db_tz = new DateTimeZone(DATETIME_STORAGE_TIMEZONE);
    if (!isset($entity->{$field_name})) {
      $entity->{$field_name} = array('und' => array());
    }
    if ($use_start) {
      $entity->{$field_name}['und'][$delta]['timezone'] = $use_start->getTimezone()->getName();
      $entity->{$field_name}['und'][$delta]['offset'] = $use_start->getOffset();
      $use_start->setTimezone($db_tz);
      $entity->{$field_name}['und'][$delta]['date'] = $use_start;
      /**
       * @todo the date_type_format line could be simplified based upon a patch
       *   DO issue #259308 could affect this, follow up on at some point.
       *   Without this, all granularity info is lost.
       *   $use_start->format(date_type_format($field['type'], $use_start->granularity));
       */
      $entity->{$field_name}['und'][$delta]['value'] = $use_start->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }
    if ($use_end) {
      // Don't ever use end to set timezone (for now)
      $entity->{$field_name}['und'][$delta]['offset2'] = $use_end->getOffset();
      $use_end->setTimezone($db_tz);
      $entity->{$field_name}['und'][$delta]['date2'] = $use_end;
      $entity->{$field_name}['und'][$delta]['value2'] = $use_end->format(DATETIME_DATETIME_STORAGE_FORMAT);
    }
  }
}
