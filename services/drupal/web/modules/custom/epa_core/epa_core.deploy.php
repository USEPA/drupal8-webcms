<?php

/**
 * @file
 */

use Drupal\group\Entity\Group;
use Drupal\media\Entity\Media;
use Drupal\search_api\Entity\Server;

/**
 *
 */
function _epa_core_populate_search_index_queue() {
  $queue = \Drupal::queue('epa_search_text_indexer');
  // Query all current revisions that lack a value in the search text field.
  $current_revs = \Drupal::database()->query(
    "SELECT vid
           FROM {node} n
           LEFT JOIN {node_revision__field_search_text} nf
           ON n.vid = nf.revision_id WHERE nf.revision_id IS NULL")
    ->fetchCol();

  $latest_revs = \Drupal::database()->query(
    "SELECT n.nid, n.vid as vid
          FROM {node_revision} n
          INNER JOIN
              (SELECT nid,
                   max(vid) AS latest_vid
              FROM {node_revision}
              GROUP BY  nid) nr_latest
              ON n.vid = nr_latest.latest_vid
          LEFT JOIN {node_revision__field_search_text} nf
              ON n.vid = nf.revision_id
          WHERE nf.revision_id IS NULL")
    ->fetchCol(1);

  // Remove current revs from latest revs.
  $latest_revs = array_diff($latest_revs, $current_revs);

  $current_revs = array_fill_keys($current_revs, 'current');
  $latest_revs = array_fill_keys($latest_revs, 'latest');
  $revisions = $current_revs + $latest_revs;

  \Drupal::logger('epa_core')->notice('Queueing ' . count($revisions) . ' revisions that need to have their search text field populated');

  foreach ($revisions as $vid => $type) {
    $queue->createItem(['vid' => $vid, 'type' => $type]);
  }
}

/**
 * Populates the search text fields for existing content.
 */
function epa_core_deploy_0001_populate_search_text(&$sandbox) {
  _epa_core_populate_search_index_queue();
}

/**
 * Sets terms with empty description to global term description token.
 */
function epa_core_deploy_0001_update_term_descriptions(&$sandbox) {
  $text = 'This page shows all of the pages at epa.gov that are tagged with \[term:name\] at this time.';
  if (!isset($sandbox['total'])) {
    // Query all terms that don't have a description set.
    $result = \Drupal::database()->query(
      'SELECT tid FROM taxonomy_term_field_data
        WHERE description__value IS NULL OR
              description__value = :value OR
              description__value REGEXP :regex', [':value' => 'This page shows all of the pages at epa.gov that are tagged with [term:name] at this time.', ':regex' => '^<p>This page shows all of the pages at epa\\.gov that are tagged with \\[term:name\\] at this time\\.<\\/p>[[:space:]]*$'])
      ->fetchCol();

    $sandbox['total'] = count($result);
    $sandbox['current'] = 0;

    \Drupal::logger('epa_core')->notice($sandbox['total'] . ' terms with outdated descriptions.');
  }

  // Query 500 at a time for batch.
  $tids = \Drupal::database()->query(
    'SELECT tid FROM taxonomy_term_field_data
        WHERE description__value IS NULL OR
              description__value = :value OR
              description__value REGEXP :regex
            LIMIT 500;', [':value' => 'This page shows all of the pages at epa.gov that are tagged with [term:name] at this time.', ':regex' => '^<p>This page shows all of the pages at epa\\.gov that are tagged with \\[term:name\\] at this time\\.<\\/p>[[:space:]]*$'])
    ->fetchCol();

  if (empty($tids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadMultiple($tids);

  foreach ($terms as $term) {
    $term->set('description', ['value' => '[term:term-description]', 'format' => 'filtered_html']);
    $term->save();
    $sandbox['current']++;
  }

  \Drupal::logger('epa_core')->notice($sandbox['current'] . ' terms descriptions updated.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}

/**
 * Explicitly sets each taxonomy term to have its path set by pathauto then re-saves
 * terms to ensure they get the latest generated path.
 */
function epa_core_deploy_0002_update_term_path(&$sandbox) {
  if (!isset($sandbox['total'])) {
    // Query all terms that don't have a description set.
    $result = \Drupal::database()->query(
      'SELECT tid FROM taxonomy_term_field_data;')
      ->fetchCol();

    $sandbox['total'] = count($result);
    $sandbox['current'] = 0;

    \Drupal::logger('epa_core')->notice($sandbox['total'] . ' term paths to be updated.');
  }

  // Query 500 at a time for batch.
  $tids = \Drupal::database()->query(
    'SELECT tid FROM taxonomy_term_field_data
        ORDER BY tid ASC
        LIMIT 500
        OFFSET ' . $sandbox['current'] . ';')
    ->fetchCol();

  if (empty($tids)) {
    $sandbox['#finished'] = 1;
    return;
  }

  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadMultiple($tids);

  foreach ($terms as $term) {
    $term->path->pathauto = 1;
    $term->save();
    $sandbox['current']++;
  }

  \Drupal::logger('epa_core')->notice($sandbox['current'] . ' term paths updated.');

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }
}

/**
 * Tag all search indexes as needing reindexing due to the changes to our
 * processor and field settings.
 */
function epa_core_deploy_refresh_indexes() {
  _epa_core_refresh_indexes('localhost');
}

/**
 * Helper function to refresh all search indexes on a server.
 */
function _epa_core_refresh_indexes($server_name) {
  $localhost = Server::load($server_name);
  foreach ($localhost->getIndexes() as $index) {
    $index->reindex();
  }
}

/**
 * Updating the field definition config for node__field_press_office cardinality
 * to be 1.
 */
function epa_core_deploy_0003_update_node_field_press_office_cardinality() {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = $manager->getFieldStorageDefinition('field_press_office', 'node');
  $storage_definition->setCardinality(1);
  $manager->updateFieldStorageDefinition($storage_definition);
}

/**
 * Moves images on banner slides to banner image field
 * and creates banner image entity where necessary.
 */
function epa_core_deploy_0003_update_banner_slide_images(&$sandbox) {
  $prefixes = ['paragraph_revision', 'paragraph'];

  $replacements = [
    ':group_type' => 'web_area-group_node-%',
    ':value' => 'banner_slide',
  ];

  if (!isset($sandbox['total'])) {
    $sandbox['total'] = 0;
    $sandbox['current'] = 0;
    $sandbox['images_created'] = 0;
    // Query all images that are being used with banner slides.
    foreach ($prefixes as $prefix) {
      $result = \Drupal::database()->query(
        'SELECT DISTINCT fi.field_image_target_id
FROM {' . $prefix . '__field_image} AS fi
LEFT JOIN {file_managed} AS fm
    ON fm.fid = fi.field_image_target_id
LEFT JOIN {' . $prefix . '__field_banner_image} AS pfb
    ON fi.revision_id = pfb.revision_id
LEFT JOIN {paragraph_revision__field_banner_slides} AS fbs
    ON fi.revision_id = fbs.field_banner_slides_target_revision_id
LEFT JOIN {node_revision__field_banner} AS nfb
    ON nfb.field_banner_target_revision_id = fbs.revision_id
LEFT JOIN {group_content_field_data} gfd
    ON gfd.entity_id = nfb.entity_id
        AND gfd.type LIKE :group_type
WHERE pfb.revision_id IS NULL
        AND fi.bundle = :value
        AND gid IS NOT NULL;', $replacements)->fetchCol();

      $sandbox['total'] += count($result);

    }

    \Drupal::logger('epa_core')->notice($sandbox['total'] . ' image files associated with banner slides.');
  }

  foreach ($prefixes as $prefix) {

    $files = \Drupal::database()->query(
      'SELECT DISTINCT fi.field_image_target_id,
         fi.field_image_alt,
         fm.filename,
         fm.langcode,
         fi.entity_id,
         fi.revision_id,
         gfd.gid
FROM {' . $prefix . '__field_image} AS fi
LEFT JOIN {file_managed} AS fm
    ON fm.fid = fi.field_image_target_id
LEFT JOIN {' . $prefix . '__field_banner_image} AS pfb
    ON fi.revision_id = pfb.revision_id
LEFT JOIN {paragraph_revision__field_banner_slides} AS fbs
    ON fi.revision_id = fbs.field_banner_slides_target_revision_id
LEFT JOIN {node_revision__field_banner} AS nfb
    ON nfb.field_banner_target_revision_id = fbs.revision_id
LEFT JOIN {group_content_field_data} gfd
    ON gfd.entity_id = nfb.entity_id
        AND gfd.type LIKE :group_type
WHERE pfb.revision_id IS NULL
        AND fi.bundle = :value
        AND gid IS NOT NULL
        LIMIT 500;', $replacements)
      ->fetchAll();

    foreach ($files as $file) {
      $banner_media = \Drupal::database()->query(
        'SELECT entity_id
          FROM media__field_media_image
          WHERE field_media_image_target_id = ' . $file->field_image_target_id . '
            AND bundle = :value;', [':value' => 'banner_image'])
        ->fetchCol();

      if (empty($banner_media)) {
        $image_media = Media::create([
          'bundle' => 'banner_image',
          'uid' => \Drupal::currentUser()->id(),
          'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
          'field_media_image' => [
            'target_id' => $file->field_image_target_id,
            'alt' => $file->field_image_alt,
            'title' => $file->filename,
          ],
        ]);
        $image_media->save();
        $langcode = $image_media->language()->getId();
        $banner_image_target_id = $image_media->id();
        if (!empty($file->gid)) {
          $group = Group::load($file->gid);
          $group->addContent($image_media, 'group_media:' . $image_media->bundle());
        }
        $sandbox['images_created']++;
      }
      else {
        $banner_image_target_id = $banner_media[0];
        $langcode = $file->langcode;
        if (empty($langcode)) {
          $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
        }
      }
      $connection = \Drupal::service('database');
      $result = $connection->insert($prefix . '__field_banner_image')
        ->fields([
          'bundle' => 'banner_slide',
          'deleted' => 0,
          'entity_id' => $file->entity_id,
          'revision_id' => $file->revision_id,
          'langcode' => $langcode,
          'delta' => 0,
          'field_banner_image_target_id' => $banner_image_target_id,
        ])
        ->execute();
      $sandbox['current']++;
    }
  }

  if ($sandbox['current'] >= $sandbox['total']) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('epa_core')->notice('Banner slide image update complete');
  }
  else {
    $sandbox['#finished'] = ($sandbox['current'] / $sandbox['total']);
  }

  \Drupal::logger('epa_core')->notice($sandbox['current'] . ' images processed / ' . $sandbox['images_created'] . ' banner images created.');

}
