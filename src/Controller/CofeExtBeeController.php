<?php

namespace Drupal\cofe_ext_bee\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use RRule\RRule;

/**
 * Returns responses for Cofe Ext BEE routes.
 */
class CofeExtBeeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Make a reservation request.
   *
   * @param int $nid
   *   Pass node id.
   * @param string $start_date
   *   Pass event start date.
   * @param string $end_date
   *   Pass event end date.
   * @param string $user_info
   *   Pass user information object string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return json data indicating reservation status.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throw exception.
   */
  public function addReservation($nid, $start_date, $end_date, $user_info) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node = $node_storage->load($nid);
    $user_data = json_decode($user_info);

    if (!empty($node) && ($node->getType() == 'product') && !empty($user_data)) {
      $avail_res = json_decode($this->checkAvailability($node->id(), $start_date, $end_date)->getContent());
      if ($avail_res->status == 200) {
        return $this->createEvent($node->id(), $start_date, $end_date, $user_data);
      }
      else {
        return new JsonResponse([
          'data' => $this->t('Failed to create event. Looks like the event may have been booked.'),
          'method' => 'GET',
          'status' => 400,
        ]);
      }
    }
    else {
      return new JsonResponse([
        'data' => $this->t('Failed to create event. Either node does not exist or it is not a product type.'),
        'method' => 'GET',
        'status' => 400,
      ]);
    }
  }

  /**
   * Return calendar reservation Ajax form.
   *
   * @param int $nid
   *   Pass node id.
   * @param string $start_date
   *   Pass event start date.
   * @param string $user_info
   *   Pass user information object string.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return form AjaxRespons
   */
  public function calendarReservationForm($nid, $start_date, $user_info) {
    $modal_content['title'] = $this->t('<h1> Reservation Form </h1>');
    $modal_content['content'] = $this->formBuilder()->getForm('Drupal\cofe_ext_bee\Form\EventBookingForm', $nid, $start_date, $user_info);

    $response = new AjaxResponse();

    if (isset($modal_content['commands'])) {
      foreach ($modal_content['commands'] as $command) {
        $response->addCommand($command);
      }
    }
    else {
      $response->addCommand(new OpenModalDialogCommand($modal_content['title'], $modal_content['content'], []));
    }

    return $response;
  }

  /**
   * Create event to bat module.
   *
   * @param int $nid
   *   Pass node id.
   * @param string $start_date
   *   Pass start date.
   * @param string $end_date
   *   Pass end date.
   * @param string $user_data
   *   Pass user string object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return json data format.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Throw exception.
   */
  public function createEvent($nid, $start_date, $end_date, $user_data) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node = $node_storage->load($nid);
    $start_date = $start_date;
    $end_date = $end_date;
    $start_date_str = $start_date;
    $end_date_str = $end_date;

    $bee_settings = $this->config('node.type.' . $node->bundle())->get('bee');

    if ($bee_settings['bookable_type'] == 'daily') {
      $start_date = new \DateTime($start_date);
      $end_date = new \DateTime($end_date);
    }

    try {
      if ($bee_settings['bookable_type'] == 'daily') {
        $booked_state = bat_event_load_state_by_machine_name('bee_daily_booked');

        if (isset($values['repeat']) && $values['repeat']) {
          $repeat_until = new \DateTime($values['repeat_until'] . 'T235959Z');
          $frequency = $this->t('Day');

          if ($values['repeat_frequency'] == 'weekly') {
            $frequency = $start_date->format('l');
          }
          elseif ($values['repeat_frequency'] == 'monthly') {
            $frequency = $this->t('@day of Month', ['@day' => $start_date->format('jS')]);
          }

          $label = $this->t('Reservations for @node Every @frequency from @start_date -> @end_date', [
            '@node' => $node->label(),
            '@frequency' => $frequency,
            '@start_date' => $start_date->format('M j Y'),
            '@end_date' => $repeat_until->format('M j Y'),
          ]);

          $rrule = new RRule([
            'FREQ' => strtoupper($values['repeat_frequency']),
            'UNTIL' => $values['repeat_until'] . 'T235959Z',
          ]);

          $event = bat_event_series_create([
            'type' => 'availability_daily',
            'label' => $label,
            'rrule' => $rrule->rfcString(),
          ]);
        }
        else {
          $event = bat_event_create(['type' => 'availability_daily']);
        }

        $event_dates = [
          'value' => $start_date->format('Y-m-d\TH:i:00'),
          'end_value' => $end_date->format('Y-m-d\TH:i:00'),
        ];
        $event->set('event_dates', $event_dates);
        $event->set('event_state_reference', $booked_state->id());
      }
      else {
        $booked_state = bat_event_load_state_by_machine_name('bee_hourly_booked');

        if (isset($values['repeat']) && $values['repeat']) {
          $repeat_until = new \DateTime($values['repeat_until'] . 'T235959Z');

          $frequency = $this->t('Day');
          if ($values['repeat_frequency'] == 'weekly') {
            $frequency = $start_date->format('l');
          }
          elseif ($values['repeat_frequency'] == 'monthly') {
            $frequency = $this->t('@day of Month', ['@day' => $start_date->format('jS')]);
          }

          $label = $this->t('Reservations for @node Every @frequency from @start_time-@end_time from @start_date -> @end_date', [
            '@node' => $node->label(),
            '@frequency' => $frequency,
            '@start_time' => $start_date->format('gA'),
            '@end_time' => $end_date->format('gA'),
            '@start_date' => $start_date->format('M j Y'),
            '@end_date' => $repeat_until->format('M j Y'),
          ]);

          $rrule = new RRule([
            'FREQ' => strtoupper($values['repeat_frequency']),
            'UNTIL' => $values['repeat_until'] . 'T235959Z',
          ]);

          $event = bat_event_series_create([
            'type' => 'availability_hourly',
            'label' => $label,
            'rrule' => $rrule->rfcString(),
          ]);
        }
        else {
          $event = bat_event_create(['type' => 'availability_hourly']);
        }

        $event_dates = [
          'value' => $start_date->format('Y-m-d\TH:i:00'),
          'end_value' => $end_date->format('Y-m-d\TH:i:00'),
        ];
        $event->set('event_dates', $event_dates);
        $event->set('event_state_reference', $booked_state->id());
      }

      if (isset($values['repeat']) && $values['repeat']) {
        $bee_settings = $this->configFactory->get('node.type.' . $node->bundle())->get('bee');

        foreach ($node->get('field_availability_' . $bee_settings['bookable_type']) as $unit) {
          if ($unit->entity) {
            $event->set('event_bat_unit_reference', $unit->entity->id());
          }
        }
      }
      else {
        $available_units = $this->getAvailableUnits($nid, $start_date_str, $end_date_str);
        $event->set('event_bat_unit_reference', reset($available_units));
      }

      if (isset($values['event_series'])) {
        $event->set('event_series', $values['event_series']);
      }

      $event->save();

      if (!empty($event) && !empty($user_data)) {
        if (!empty($user_data->firstname) && !empty($user_data->email)) {
          $this->entityTypeManager()->getStorage('cofe_ext_bee')->create([
            'id' => $event->id(),
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
            'booked_by_email' => $user_data->email,
            'booked_by_name' => $user_data->firstname,
            'status' => 0,
          ])->save();
        }
      }

      return new JsonResponse([
        'data' => [
          'event' => $event->toArray(),
          'owner' => $event->getOwner()->toArray(),
        ],
        'method' => 'GET',
        'status' => 200,
      ]);
    }
    catch (Exception $e) {
      $this->logger('cofe_ext_bee')->notice('Failed to create reservation');
      return new JsonResponse([
        'data' => [
          'message' => $this->t('falied to create reservation'),
        ],
        'method' => 'GET',
        'status' => 400,
      ]);
    }
  }

  /**
   * Get available Units.
   *
   * @param int $nid
   *   Pass node id.
   * @param string $start_date
   *   Pass unit event series start date.
   * @param string $end_date
   *   Pass unit event series end date.
   *
   *   Return array.
   */
  public function getAvailableUnits($nid, $start_date, $end_date) {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node = $node_storage->load($nid);
    $bee_settings = $this->config('node.type.' . $node->bundle())->get('bee');
    $units_ids = [];
    $field_availaibility = $node->get('field_availability_' . $bee_settings['bookable_type']);

    if (isset($field_availaibility)) {
      foreach ($field_availaibility as $unit) {
        if ($unit->entity) {
          $units_ids[] = $unit->entity->id();
        }
      }
      if ($bee_settings['bookable_type'] == 'daily') {
        $start_date = new \DateTime($start_date);
        $end_date = new \DateTime($end_date);
        $end_date->sub(new \DateInterval('PT1M'));
        $available_units_ids = bat_event_get_matching_units($start_date, $end_date, ['bee_daily_available'], $bee_settings['type_id'], 'availability_daily');
      }
      else {
        $start_date = new \DateTime($start_date->format('Y-m-d H:i'));
        $end_date = new \DateTime($end_date->format('Y-m-d H:i'));
        $end_date->sub(new \DateInterval('PT1M'));
        $available_units_ids = bat_event_get_matching_units($start_date, $end_date, ['bee_hourly_available'], $bee_settings['type_id'], 'availability_hourly');
      }

      $intersect = array_intersect($units_ids, $available_units_ids);

      if (is_array($intersect) && !empty($intersect)) {
        return array_intersect($units_ids, $available_units_ids);
      }
      else {
        throw new Exception('Event will overlap');
      }
    }
    else {
      throw new Exception('Field field_availability can not be found');
    }
  }

  /**
   * Check availabilty of an event.
   *
   * @param int $nid
   *   Node ID.
   * @param string $start_date
   *   Event start date.
   * @param string $end_date
   *   Event end date.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   return json data of availability of an event with status code.
   */
  public function checkAvailability($nid, $start_date, $end_date) {
    try {
      return new JsonResponse([
        'data' => [
          'message' => $this->t('reservation will not overlap'),
          'event' => $this->getAvailableUnits($nid, $start_date, $end_date)[0],
        ],
        'method' => 'GET',
        'status' => 200,
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'data' => [
          'message' => $this->t('Message: @message', ['@message' => $e->__toString()]),
        ],
        'method' => 'GET',
        'status' => 400,
      ]);
    }
  }

}
