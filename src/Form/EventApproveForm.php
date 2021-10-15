<?php

namespace Drupal\cofe_ext_bee\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for approving a cofe_ext_bee entity type.
 *
 * @ingroup cofe_ext_bee.
 */
class EventApproveForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to approve entity %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Approve');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.cofe_ext_bee.canonical');
  }

  /**
   * {@inheritdoc}
   *
   * Approve the entity and redirect to cofe event list.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->setStatus(1);
    \Drupal::messenger()->addMessage($this->t('Event id @id as been approved.', ['@id' => $this->entity->id()]));
    $url = Url::fromRoute('entity.cofe_ext_bee.canonical');
    $form_state->setRedirectUrl($url);
  }

}
