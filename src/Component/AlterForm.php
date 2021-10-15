<?php

namespace Drupal\cofe_ext_bee\Component;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class to pass user value before webform page element loads.
 */
class AlterForm {

  /**
   * Validation method call.
   *
   * @param object $form
   *   Pass in form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Pass in form state.
   */
  public static function validateForm(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    CalendarRenderer::setUser($values);
  }

}
