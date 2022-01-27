<?php

namespace Drupal\epa_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\paragraphs_library\LibraryItemAccessControlHandler;

/**
 * Access controller for the paragraphs entity.
 *
 * @see \Drupal\paragraphs\Entity\Paragraph.
 */
class EpaCoreLibraryItemAccessControlHandler extends LibraryItemAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $library_item, $operation, AccountInterface $account) {
    // In case a library item is unpublished, only allow access if a user has
    // administrative permission. Ensure to collect the required cacheability
    // metadata and combine both the published and the referenced access check
    // together, both must allow access if unpublished.
    $access = AccessResult::allowed()->addCacheableDependency($library_item);
    if ($operation === 'view' && !$library_item->isPublished()) {
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
    }
    
    // Users can update if they have 'edit paragraph library item' permission
    // and they own the paragraph; or if they have admin permission.
    if ($operation === 'update') {
      $access = $access->allowedIf($library_item->getOwnerId() == $account->id());
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, 'edit own paragraph library items'));
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, 'edit paragraph library item'));
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
    }

    // Only users with delete paragraph library items permissino
    // or admin permission can delete library items.
    if ($operation === 'delete') {
      $access = $access->andIf(AccessResult::allowedIfHasPermission($account, 'delete paragraph library items'));
      $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
    }
    return $access;
  }

}
