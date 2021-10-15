<?php

namespace Drupal\cofe_ext_bee\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
 * ModalForm class.
 */
class EventBookingForm extends FormBase {

  /**
   * Node Object.
   *
   * @var Drupal\node\NodeInterface
   */
  public $node;

  /**
   * Event Start Date.
   *
   * @var int
   */
  public $starttime;

  /**
   * Event End Date.
   *
   * @var int
   */
  public $endtime;

  /**
   * Minimum End Date.
   *
   * @var int
   */
  public $minDate;

  /**
   * Maximum date.
   *
   * @var int
   */
  public $maxDate;

  /**
   * Node Entity Type Min Start Date Field.
   *
   * @var int
   */
  public $nodeMinStart;

  /**
   * Node Entity Type Max End Date Field.
   *
   * @var int
   */
  public $nodeMaxEnd;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modal_form_modal_form';
  }

  /**
   * Build form elements.
   *
   * @param array $form
   *   Pass form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Pass form state object.
   * @param int $nid
   *   Pass node id.
   * @param int $start_date
   *   Pass start date.
   * @param array|string $user_info
   *   Pass user information.
   *
   * @return array
   *   Returm form object.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = 1, $start_date = 0, $user_info = '') {
    $node = Node::load($nid);
    if ($node instanceof NodeInterface) {
      $this->node = $node;
      if ($node->hasField('field_minimum_start') && $node->hasField('field_maximum_end')) {
        if (!$node->get('field_minimum_start')->isEmpty() && !$node->get('field_maximum_end')->isEmpty()) {
          $this->nodeMinStart = $node->get('field_minimum_start')->getValue()[0]['value'];
          $this->nodeMaxEnd = $node->get('field_maximum_end')->getValue()[0]['value'];
        }
      }
    }

    $form['#prefix'] = '<div id="modal_example_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    // A required checkbox field.
    $form['mark'] = [
      '#type' => 'html_tag',
      '#tag'  => 'h3',
      '#value' => $this->t('You are about to book @node_title. Your booking will start from @start_date and end on selected date below.', [
        '@node_title' => $this->node->getTitle(),
        '@start_date' => $start_date,
      ]),
    ];

    $this->starttime = new \DateTime($start_date);
    $this->endtime = new \DateTime($end_date);

    $this->setMinDate($this->nodeMinStart);
    $this->setMaxDate($this->nodeMaxEnd);

    $form['end_date'] = [
      '#type' => 'select',
      '#title' => $this->t('End Date'),
      '#options' => $this->optionEndDates(),
    ];

    $form['nid'] = [
      '#type' => 'hidden',
      '#value' => $this->node->id(),
    ];

    $form['start_date'] = [
      '#type' => 'hidden',
      '#value' => $start_date,
    ];

    $form['user_info'] = [
      '#type' => 'hidden',
      '#value' => $user_info,
    ];

    $form['actions']['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit modal form'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * Calculate selectable duration date.
   *
   * @return int
   *   Return selectable duration day in int.
   */
  private function getSelectableDuration() {
    return ($this->maxDate - $this->minDate) / 60 / 60 / 24;
  }

  /**
   * Set minimun date property.
   *
   * @param int $days
   *   Pass days integer value.
   */
  private function setMinDate($days) {
    $this->minDate = strtotime($this->starttime->format('Y-m-d') . ' + ' . $days . ' days');
  }

  /**
   * Set maximum day property.
   *
   * @param int $days
   *   Pass days integer value.
   */
  private function setMaxDate($days) {
    $this->maxDate = strtotime($this->starttime->format('Y-m-d') . ' + ' . $days . ' days');
  }

  /**
   * Return date format base on format input arguement.
   *
   * @param int $days
   *   Pass days integer value.
   * @param string $format
   *   Parse date format string.
   *
   * @return string
   *   Return date format string.
   */
  private function getOptionDateFormat($days, $format) {
    $date_object = new \DateTime();
    $date_object->setTimestamp(strtotime($this->starttime->format('Y-m-d') . ' + ' . $days . ' days'));
    return $date_object->format($format);
  }

  /**
   * Return a key value pair of option array.
   *
   * @return array
   *   Return an array of date options.
   */
  private function optionEndDates() {
    $options = [];
    for ($i = 1; $i < $this->getSelectableDuration(); $i++) {
      $options[$this->getOptionDateFormat($i, 'Y-m-d')] = $this->getOptionDateFormat($i, 'Y/m/d');
    }
    return $options;
  }

  /**
   * Return Ajax form template.
   *
   * @param array $form
   *   Pass form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Pass form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Return Ajax form response
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $response = new AjaxResponse();
    $client = \Drupal::httpClient();
    $values = $form_state->getValues();
    $nid = $values['nid'];
    $start_date = $values['start_date'];
    $end_date = $values['end_date'];
    $user_info = $values['user_info'];
    $url = "$base_url/cofe-ext-bee/add-reservation/$nid/$start_date/$end_date/$user_info";
    $request = $client->get($url);
    $urlRresponse = $request->getBody()->getContents();

    if ($urlRresponse != NULL) {
      $data = json_decode($urlRresponse);
      $response->addCommand(new CloseModalDialogCommand());

      if ($data->status === 200) {
        $status = $this->t('Success');
        $message = $this->t('An event has been created for @nid @start_date to @end_date', [
          '@nid' => $nid,
          '@start_date' => $start_date,
          '@end_date' => $end_date,
        ]);
        $response->addCommand(new OpenModalDialogCommand($status, $message, ['width' => 800]));
      }
      else {
        $status = $this->t('Fail');
        $message = $this->t('Sorry that event will overlap. Status @status', ['@status' => $data->status]);
        $response->addCommand(new OpenModalDialogCommand($status, $message, ['width' => 800]));
      }
    }
    else {
      $message = $this->t('Something is wrong with the response content');
      $response->addCommand(new OpenModalDialogCommand($message, ['width' => 800]));
    }
    return $response;
  }

  /**
   * Process form data on submission.
   *
   * @param array $form
   *   Pass form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   Pass form state object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->logger('cofe_ext_bee')->notice('Form has been submitted succesfully');
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['config.modal_form_example_modal_form'];
  }

}
