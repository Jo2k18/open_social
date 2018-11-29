<?php

namespace Drupal\activity_basics\Plugin\ActivityContext;

use Drupal\activity_creator\Plugin\ActivityContextBase;
use Drupal\activity_creator\ActivityFactory;

/**
 * Provides a 'OrganizerActivityContext' activity context.
 *
 * @ActivityContext(
 *  id = "organizer_activity_context",
 *  label = @Translation("Organizer activity context"),
 * )
 */
class OrganizerActivityContext extends ActivityContextBase {

  /**
   * {@inheritdoc}
   */
  public function getRecipients(array $data, $last_uid, $limit) {
    $recipients = [];

    // We only know the context if there is a related object.
    if (isset($data['related_object']) && !empty($data['related_object'])) {
      $related_entity = ActivityFactory::getActivityRelatedEntity($data);
      if ($data['related_object'][0]['target_type'] === 'event_enrollment') {
        $recipients = $this->getRecipientOrganizerFromEntity($related_entity, $data);
      }
    }

    return $recipients;
  }

  /**
   * Returns Organizer recipient from Events.
   */
  public function getRecipientOrganizerFromEntity(array $related_entity, array $data) {
    $recipients = [];

    // Don't return recipients if user comments on own content.
    $original_related_object = $data['related_object'][0];
    if (isset($original_related_object['target_type'])
      && $original_related_object['target_type'] === 'event_enrollment'
      && $related_entity !== null) {
      $storage = \Drupal::entityTypeManager()->getStorage($related_entity['target_type']);
      $event = $storage->load($related_entity['target_id']);

      if ($event === null) {
        return $recipients;
      }

      $recipients[] = [
        'target_type' => 'user',
        'target_id' => $event->getOwnerId(),
      ];
    }

    // If there are any others we should add. Make them also part of the
    // recipients array.
    \Drupal::moduleHandler()
      ->alter('activity_recipient_organizer', $recipients, $event);

    return $recipients;
  }

}
