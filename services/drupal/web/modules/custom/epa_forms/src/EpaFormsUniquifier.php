<?php

namespace Drupal\epa_forms;

use Drupal\Component\Utility\Unicode;

/**
 *
 */
class EpaFormsUniquifier {

  /**
   *
   */
  public static function uniquifyFormId($id) {
    $maxlength = 32;
    $new_id = Unicode::truncate($id, $maxlength, FALSE);

    $storage = \Drupal::entityTypeManager()->getStorage('webform');
    if (is_null($storage->load($new_id))) {
      return $new_id;
    }

    $i = 0;
    do {
      // Append an incrementing numeric suffix until we find a unique alias.
      $unique_suffix = '_' . $i;
      $new_id = Unicode::truncate($id, $maxlength - mb_strlen($unique_suffix), FALSE) . $unique_suffix;
      $i++;
    } while (!is_null($storage->load($new_id)));

    return $new_id;
  }

  /**
   *
   */
  public static function getFormIdForNode($entity) {
    $title = $entity->label();
    $machine_name = '';
    if ($entity->hasField('field_machine_name')) $machine_name = $entity->field_machine_name->value;
    $label = !empty($machine_name) ? $machine_name : $title;
    $id = preg_replace('@[^a-z0-9-]+@', '_', strtolower($label));
    return EpaFormsUniquifier::uniquifyFormId($id);
  }

}
