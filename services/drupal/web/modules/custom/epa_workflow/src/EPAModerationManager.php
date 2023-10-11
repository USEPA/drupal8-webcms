<?php

namespace Drupal\epa_workflow;

use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles moderation events.
 */
class EPAModerationManager {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * List of moderations to process.
   *
   * @var \Drupal\epa_workflow\EPAModerationInterface
   */
  protected $moderations = [];

  /**
   * EPAModerationManager constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * Perform tasks after entity is moderated.
   *
   * @param Drupal\content_moderation\Entity\ContentModerationStateInterface $moderation_entity
   *   The moderation state.
   */
  public function processModeration(ContentModerationStateInterface $moderation_entity) {
    $state = $moderation_entity->moderation_state->value;
    if (!empty($this->moderations[$state])) {
      $moderation = $this->moderations[$state];
    }
    else {
      $moderation = $this->moderations['base'];
    }

    // @todo Should consider trying to refactor this to move some of the work
    // done in the process methods to be called in a presave hook. We can probably
    // avoid the additional save here in a lot of cases.
    $moderation->process($moderation_entity);
    // @todo Look to alter the moderation state with an additional property so that
    // we can key off that in the DANSE event subscriber as this is causing duplicate
    // events to be created, however, this breaks the moderation processes.
    $moderation->save();
    $moderation->logTransition();
  }

  /**
   * Adds a moderation.
   */
  public function addModeration(EPAModerationInterface $moderation) {
    $this->moderations[$moderation->getModerationName()] = $moderation;
  }

  /**
   * Check if content is moderated.
   *
   * @param Drupal\content_moderation\Entity\ContentModerationStateInterface $moderation_entity
   *   The moderation state.
   */
  public function isModeratedEntity(ContentModerationStateInterface $moderation_entity) {
    $content_entity_type_id = $moderation_entity->content_entity_type_id->value;
    $content_entity_type = $this->entityTypeManager->getStorage($content_entity_type_id)->getEntityType();
    return $this->moderationInformation->isModeratedEntityType($content_entity_type);
  }

  /**
   * Get content entity revision from moderation state.
   *
   * @param Drupal\content_moderation\Entity\ContentModerationStateInterface $moderation_entity
   *   The moderation state.
   *
   * @return Drupal\Core\Entity\ContentEntityInterface
   *   Return latest content entity revision.
   */
  public function getContentEntityRevision(ContentModerationStateInterface $moderation_entity) {
    $content_entity_type = $moderation_entity->content_entity_type_id->value;
    $content_entity_revision_id = $moderation_entity->content_entity_revision_id->value;

    $content_entity_revision = $this->entityTypeManager->getStorage($content_entity_type)->loadRevision($content_entity_revision_id);

    return $content_entity_revision;
  }

}
