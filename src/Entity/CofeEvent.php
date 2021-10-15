<?php

namespace Drupal\cofe_ext_bee\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ContentEntityExample entity.
 *
 * @ContentEntityType(
 *    id = "cofe_ext_bee",
 *    label = @Translation("cofe Event entity"),
 *    handlers = {
 *      "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *      "list_builder" = "Drupal\cofe_ext_bee\Controller\CofeEventListBuilder",
 *      "form" = {
 *        "default" = "Drupal\cofe_ext_bee\Form\EventForm",
 *        "edit" = "Drupal\cofe_ext_bee\Form\EventForm",
 *        "approve" = "Drupal\cofe_ext_bee\Form\EventApproveForm",
 *        "reject" = "Drupal\cofe_ext_bee\Form\EventRejectForm",
 *      },
 *    },
 *    base_table = "cofe_ext_bee",
 *    admin_permission = "administer cofe_ext_bee configuration",
 *    entity_keys = {
 *      "id" = "id",
 *      "start_date" = "start_date",
 *      "end_date" = "end_date",
 *      "booked_by_email" = "booked_by_email",
 *      "booked_by_name" = "booked_by_name",
 *      "status" = "status"
 *    },
 *    links = {
 *      "canonical" = "/admin/event/{cofe_ext_bee}",
 *      "edit-form" = "/admin/event/{cofe_ext_bee}/edit",
 *      "approve-form" = "/admin/event/{cofe_ext_bee}/approve",
 *      "reject-form" = "/admin/event/{cofe_ext_bee}/reject",
 *    }
 * )
 */
class CofeEvent extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // Default author to current user.
    $values += [
      'id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStartDate() {
    $date = new \DateTime($this->get('start_date')->value);
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndDate() {
    $date = new \DateTime($this->get('end_date')->value);
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->get('booked_by_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('booked_by_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    $status = $this->get('status')->value;
    $value;
    switch ($status) {
      case 0:
        $value = 'pending';
        break;

      case 1;
        $value = 'approved';
        break;

      case 2;
        $value = 'rejected';
        break;

      default:
        $value = 'pending';
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status)->save();
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Event ID'))
      ->setDescription(t('The ID of the Term entity.'))
      ->setReadOnly(TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Start Date'))
      ->setDescription(t('Event start date timestamp.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Start Date'))
      ->setDescription(t('Event end date timestamp.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['booked_by_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setDescription(t('User booking email.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['booked_by_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the booker.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('Status of event relating to admin action.'))
      ->setSettings([
        'default_value' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

}
