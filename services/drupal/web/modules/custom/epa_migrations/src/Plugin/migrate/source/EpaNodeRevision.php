<?php

namespace Drupal\epa_migrations\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\NodeRevision;
use Drupal\migrate\Row;

/**
 * Load node revisions that will be migrated into fields.
 *
 * @MigrateSource(
 *   id = "epa_node_revision",
 *   source_module = "node"
 * )
 */
class EpaNodeRevision extends NodeRevision {

  /**
   * {@inheritDoc}
   */
  protected $batchSize = 1000;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Get the default Node query.
    $query = parent::query();

    $query->leftJoin('panelizer_entity', 'pe', 'n.vid = pe.revision_id AND pe.entity_id = n.nid AND pe.entity_type = :type', [':type' => 'node']);
    $query->leftJoin('panels_display', 'pd', 'pe.did = pd.did');

    // Only include records where one of the following is true:
    // * There's no layout record (no panelizer override)
    // * Uses panelizer onecol_page layotu
    // * Users panelizer twocol_page layout and IS NOT a web area node
    $and = $query->andConditionGroup()
      ->condition('pd.layout', 'twocol_page')
      ->condition('n.type', 'web_area', '!=');
    $or = $query->orConditionGroup()
      ->condition('pe.did', NULL, 'IS NULL')
      ->condition('pe.did', 0)
      ->condition('pd.layout', 'onecol_page')
      ->condition($and);

    $query->condition($or);
    return $query;
  }

  /**
   * {@inheritDoc}
   */
  public function prepareRow(Row $row) {
    // Always include this fragment at the beginning of every prepareRow()
    // implementation, so parent classes can ignore rows.
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }

    // Get the revision moderation state and timestamp.
    $state_data = $this->select('node_revision_epa_states', 'nres')
      ->fields('nres', ['state'])
      ->condition('nres.vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchAll();

    if ($state_data) {
      $state_data = array_shift($state_data);
      $state_map = [
        'unpublished' => 'unpublished',
        'draft' => 'draft',
        'published' => 'published',
        'draft_approved' => 'draft_approved',
        'published_review' => 'published_needs_review',
        'published_expire' => 'published_day_til_expire',
        'draft_review' => 'draft_needs_review',
        'queued_for_archive' => 'unpublished',
      ];

      $row->setSourceProperty('nres_state', $state_map[$state_data['state']]);
    }

    // To prepare rows for import into fields, we're going to:
    // - Skip nodes that use panelizer and have a layout other than onecol_page
    //   or twocol_page.
    // - Add a 'layout' source property to populate the 'field_layout'.
    // - Add source properties containing query results for 'main_col' and
    //   'sidebar' panes.
    //
    // First, initialize the 'layout' source property as NULL so we can properly
    // process nodes that do not have a record in the 'panelizer_entith' table.
    $row->setSourceProperty('layout', NULL);

    // Get the Display ID for the current revision.
    $did = $this->select('panelizer_entity', 'pe')
      ->fields('pe', ['did'])
      ->condition('pe.revision_id', $row->getSourceProperty('vid'))
      ->condition('pe.entity_id', $row->getSourceProperty('nid'))
      ->condition('pe.entity_type', 'node')
      ->execute()
      ->fetchField();

    // Get the node type from configuration.
    $type = $row->getSourceProperty('type');

    if ($did) {
      // Get the Panelizer layout for this display.
      $layout = $this->select('panels_display', 'pd')
        ->fields('pd', ['layout'])
        ->condition('pd.did', $did)
        ->execute()
        ->fetchField();

      // Update the 'layout' property to its actual value.
      $row->setSourceProperty('layout', $layout);

      // Fetch the main_col panes and add the result as a source property.
      $main_col_panes = $this->fetchPanes('main_col', $did);
      $row->setSourceProperty('main_col_panes', $main_col_panes);

      // Fetch the sidebar panes and add the result as a source property.
      $sidebar_panes = $this->fetchPanes('sidebar', $did);
      $row->setSourceProperty('sidebar_panes', $sidebar_panes);
    }

    return parent::prepareRow($row);
  }

  /**
   * Given a panel machine name and did, fetch panes.
   *
   * @param string $panel
   *   The machine name of the panel from which to select panes.
   * @param int $did
   *   The Display ID for this node.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A prepared statement, or NULL if the query is not valid.
   */
  private function fetchPanes($panel, $did) {
    return $this->select('panels_pane', 'pp')
      ->fields('pp')
      ->condition('pp.did', $did)
      ->condition('pp.panel', $panel)
      ->execute()
      ->fetchAll();
  }

}
