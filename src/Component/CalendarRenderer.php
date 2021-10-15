<?php

namespace Drupal\cofe_ext_bee\Component;

use Drupal\Core\Render\Element\Details;
use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\node\Entity\Node;

/**
 * Generate and seed in necessary data to pre render calendar object.
 */
class CalendarRenderer extends Details {

  /**
   * Node ID.
   *
   * @var int
   */
  public static $nid;

  /**
   * Node object.
   *
   * @var Object
   */
  public static $node;

  /**
   * Unit ID.
   *
   * @var array
   */
  public static $unitId;

  /**
   * Calendar ID.
   *
   * @var int
   */
  public static $calendarId;

  /**
   * User Settings.
   *
   * @var array
   */
  public static $userSettings;

  /**
   * HTML Render attributes.
   *
   * @var array
   */
  public static $attr;

  /**
   * Hold calendar duration.
   *
   * @var int
   */
  public static $duration;

  /**
   * Hold user information.
   *
   * @var array
   */
  public static $userInfo;

  /**
   * Set user infor from personal information webform wizard page element.
   *
   * @param array $user_info
   *   Pass user information.
   */
  public static function setUser(array $user_info) {
    self::$userInfo = $user_info;
  }

  /**
   * Pre render calendar to webform wizard page element.
   *
   * @param array $element
   *   Pass pre render element.
   *
   * @return array[]|mixed
   *   Return render array for calendar.
   */
  public static function preRenderCalendar(array $element) {
    if (isset($element['#webform_key'])) {

      if ($element['#webform_key'] == 'reservation_calendar') {
        self::$nid = $element['node']['#default_value'];
        self::$node = Node::load(self::$nid);

        if (isset($element['span'])) {
          if (isset($element['span']['#default_value'])) {
            self::$duration = $element['span']['#default_value'];
          }
        }

        if (is_array(self::$node->toArray()) && !empty(self::$node->toArray())) {
          if (is_array(self::$node->toArray()['field_availability_daily'])) {
            self::$calendarId = self::$node->toArray()['field_availability_daily'][0]['target_id'];
            if (isset(self::$node->toArray()['type'][0])) {
              $unitTitle = ucwords(self::$node->toArray()['type'][0]['target_id']);
              self::$unitId = array_search($unitTitle, bat_unit_types_ids());
            }
          }
        }

        self::userSettings();
        self::setAttr();

        $settings = \Drupal::moduleHandler()->invoke('bat_fullcalendar', 'configure', [self::$userSettings]);
        $settings['batCalendar'][0]['id'] = self::$attr['id'];
        $settings['batCalendar'][0]['nid'] = self::$nid;
        $settings['batCalendar'][0]['duration'] = self::$duration;
        $settings['batCalendar'][0]['user_info'] = self::$userInfo ? json_encode(self::$userInfo) : '';
        $attributes = new Attribute(self::$attr);

        $element = [
          'calendar' => [
            '#markup' => '<div' . $attributes->__toString() . '></div>',
            '#cache' => [
              'max-age' => '0',
            ],
          ],
          '#attached' => [
            'library' => ['cofe_ext_bee/cofe_ext_bee'],
            'drupalSettings' => $settings,
          ],
        ];
      }
    }

    return $element;
  }

  /**
   * Create default user setting for calendar renderer.
   */
  public static function userSettings() {
    self::$userSettings['batCalendar'][] = [
      'unitType' => self::$unitId,
      'unitIds' => self::$calendarId,
      'eventType' => 'availability_daily',
      'eventGranularity' => 'bat_daily',
      'editable' => TRUE,
      'selectable' => TRUE,
      'defaultView' => 'month',
      'views' => 'month',
      'headerLeft' => 'today',
      'headerCenter' => 'title',
      'headerRight' => 'prev, next',
      'resourceLabelText' => '',
      'background' => 'hide-book',
    ];
  }

  /**
   * Set renderer asttributes.
   */
  public static function setAttr() {
    self::$attr = [
      'id' => Html::getUniqueId('calendar'),
      'class' => [
        'calendar-set',
        'clearfix',
      ],
    ];
  }

}
