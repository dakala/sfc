<?php

/**
 * @file
 * Contains \Drupal\sfc\Controller\SimpleFullcalendar.
 */

namespace Drupal\sfc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class SimpleFullcalendar extends ControllerBase {

  /**
   * Display the calendar.
   */
  public function calendar() {
    $build = [];
    $build['content'] = [
      '#markup' => '<div id="calendar"></div>',
    ];
    // Attach library containing css and js files.
    $build['#attached']['library'][] = 'sfc/sfc.calendar';
    return $build;
  }

  /**
   * Get events for the calendar.
   */
  public function json(Request $request) {
    header('Content-Type: application/json');

    $events = $this->getCalendarEvents($request);
    print json_encode($events);
    exit();
  }


  /**
   * Process request for events.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return array
   */
  protected function getCalendarEvents(Request $request) {
    global $base_url;

    if (!empty($type = $request->get('type'))) {
      $settings[$type] = \Drupal::configFactory()->getEditable('sfc.settings')->get('sfc_entity.' . $type);
    }
    else {
      $settings = \Drupal::configFactory()->getEditable('sfc.settings')->get('sfc_entity');
    }

    $nids = [];
    $factory = \Drupal::service('entity.query');
    if(!empty($settings)) {
      foreach ($settings as $type => $definition) {
        if ($definition['enabled'] == 1) {
          $query = $factory->get('node')
            ->condition('type', $type)
            ->condition('status', 1)
            ->accessCheck(TRUE);

          if ($definition['limit'] > 0) {
            $query->range(0, $definition['limit']);
          }
          $nids += $query->execute();
        }
      }
    }

    $events = [];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
    foreach($nodes as $node) {
      $date_fields = $this->getDateFields($node->bundle());

      $start = $date_fields[$node->bundle()]['start'];
      $end = $date_fields[$node->bundle()]['end'];

      $events[]  = [
        'title' => $node->title->value,
        'id' => $node->id(),
        'url' => $base_url . '/node/' . $node->id(),
        'start' => $node->{$start}->value,
        'end' => $node->{$end}->value,
        'color' => $settings[$node->bundle()]['color'],
        'textColor' => $settings[$node->bundle()]['textColor'],
      ];
    }
    return $events;
  }

  /**
   * Get start and end date fields for the given entity bundle.
   *
   * @param $bundle string
   *  The name of the entity bundle.
   *
   * @return array
   *  Associative array of start and end dates keyed by the bundle name.
   */
  protected function getDateFields($bundle) {
    $date_fields = &drupal_static(__FUNCTION__, []);

    if (empty($date_fields[$bundle])) {
      $entity_display = \Drupal::service('entity.manager')
        ->getStorage('entity_view_display')
        ->load('node.' . $bundle . '.default');
      $fields = $entity_display->get('content');
      foreach ($fields as $field_name => $field_settings) {
        if (!empty($date_field = $field_settings['third_party_settings']['sfc']['sfc_date_field_setting'])) {
          $date_fields[$bundle][$date_field] = $field_name;
        }
      }
    }
    return $date_fields;
  }
}
