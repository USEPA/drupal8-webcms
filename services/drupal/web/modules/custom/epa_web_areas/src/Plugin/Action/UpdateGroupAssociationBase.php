<?php

namespace Drupal\epa_web_areas\Plugin\Action;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class UpdateGroupAssociationBase extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The target group entity we're wanting to switch to.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $targetGroup;

  const DENIED = 'denied';

  const SUCCESS = 'success';

  const SPECIAL_DENIED = 'special_denied';


  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('group.membership_loader'),
      $container->get('config.factory'),
      $container->get('messenger'),
    );
  }

  /**
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, GroupMembershipLoaderInterface $group_membership_loader, ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'updated_group' => NULL
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['updated_group'] = [
      '#title' => $this->t('Updated Group'),
      '#type' => 'select',
      '#options' => $this->getGroupOptions(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['updated_group'],
    ];
    return $form;
  }

  public function getGroupOptions() {
    $options = [];
    if (array_intersect($this->currentUser->getRoles(), ['administrator', 'system_webmaster'])) {
      $groups = Group::loadMultiple();
      foreach ($groups as $group) {
        $options[$group->id()] = "{$group->label()} ({$group->id()})";
      }
    }
    else {
      // Get all groups the current user belongs to
      $memberships = $this->groupMembershipLoader->loadByUser();
      foreach ($memberships as $group) {
        $options[$group->getGroup()->id()] = "{$group->getGroup()->label()} ({$group->getGroup()->id()})";
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['updated_group'] = $form_state->getValue('updated_group');
    $this->targetGroup = Group::load($this->configuration['updated_group']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $config = $this->configFactory->get('epa_web_areas.allowed_bulk_change');

    // $object is not allowed based on config.
    if ($config->get($object->getEntityTypeId()) && !in_array($object->bundle(), $config->get($object->getEntityTypeId()))) {
      $this->context['denied'][] = $object->getTitle();
      return $return_as_object ? new AccessResultForbidden() : FALSE;
    }

    // News Releases, Perspectives, and Speeches & Remarks are special in that
    // they are only allowed if enabled on the specific Web Area.
    // If the $object is one of those three bundles we need to check that
    // bundle is allowed on the target Group selected in the VBO form.
    $special_types = ['news_release', 'perspective', 'speeches'];
    if ($object->getEntityTypeId() == 'node' && in_array($object->bundle(), $special_types) && !$this->groupAllowsBundle($object->bundle())) {
      $this->context['special_denied'][] = $object->getTitle();
      return $return_as_object ? new AccessResultForbidden() : FALSE;
    }

    // Check if the object we're updating has a group associated with it, i.e GroupContent entity.
    // If it does not then we need to check if the current user is an admin or system_webmaster as those
    // are the only users to allow associated 'orphaned' content with a new Web Area.
    $group_contents = GroupContent::loadByEntity($object);
    $allowed_roles = ['administrator', 'system_webmaster'];

    if ($group_contents || array_intersect($account->getRoles(), $allowed_roles)) {
      $access = $object->access('update', $account, TRUE);
      return $return_as_object ? $access : $access->isAllowed();
    }

    $message = new TranslatableMarkup('Your account does not have access to add a web area association to this content. Contact Web_CMS_Support@epa.gov for help.');
    return $return_as_object ? new AccessResultForbidden($message->render()) : FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function executeMultiple(array $entities) {
    parent::executeMultiple($entities);
    $this->processMessages(UpdateGroupAssociationBase::DENIED, 'Unable to move @items as you do not have access to these nodes.');
    $this->processMessages(UpdateGroupAssociationBase::SPECIAL_DENIED, 'Unable to move @items as the @group Web Area does not allow creating these nodes.');
    $this->processMessages(UpdateGroupAssociationBase::SUCCESS, 'Successfully moved @items to the new @group Web Area. Review the menu links from the previously associated Web Area.');
  }

  /**
   * Process messages for a specific context key.
   *
   * @param string $key
   *   The key in $this->context to process.
   * @param string $message
   *   The message to display when processing messages.
   */
  public function processMessages($key, $message) {
    if (isset($this->context[$key]) && !empty($this->context[$key])) {
      $items = implode(', ', $this->context[$key]);
      $items_message = rtrim($items, ', ');

      if (count($this->context[$key]) > 5) {
        $items_message .= ' and more...';
      }

      // @todo: See if we can set a property with the loaded group.
      $group = Group::load($this->configuration['updated_group'])->label();

      $message = str_replace(['@items', '@group'], [$items_message, $group], $message);

      // Based on message type display error or success message.
      switch ($key) {
        case UpdateGroupAssociationBase::DENIED:
        case UpdateGroupAssociationBase::SPECIAL_DENIED:
          $this->messenger->addError($message);
          break;
        case UpdateGroupAssociationBase::SUCCESS:
          $this->messenger->addStatus($message);
          break;
      }

    }
  }


  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Get the GroupContent from the node and update it using the new group from the 'updated_group' configuration.
    $group_contents = GroupContent::loadByEntity($entity);
    if ($group_contents) {
      foreach ($group_contents as $group_content) {
        $group_content->get('gid')->setValue($this->configuration['updated_group']);
        $group_content->save();
      }
      $this->context['success'][] = $entity->getEntityTypeId() == 'node' ? $entity->getTitle() : $entity->label();
    }
    else {
      $values = [
        'type' => $entity->getEntityTypeId() == 'node' ? 'web_area-group_node-' . $entity->bundle() : 'web_area-group_media-' . $entity->bundle(),
        'uid' => 0,
        'gid' => $this->configuration['updated_group'],
        'entity_id' => $entity->id(),
        'entity_type' => $entity->getEntityTypeId(),
        'label' =>  $entity->getEntityTypeId() == 'node' ? $entity->getTitle() : $entity->label(),
      ];
      // Means it was never associated with a group
      GroupContent::create($values)->save();
      $this->context['success'][] = $entity->getEntityTypeId() == 'node' ? $entity->getTitle() : $entity->label();
    }
  }


  /**
   * Method for checking our special content types against the Group to see if they are allowed.
   *
   * @param string $bundle
   *
   * @return mixed|true
   */
  public function groupAllowsBundle(string $bundle): mixed {
    $group = Group::load($this->configuration['updated_group']);

    return match ($bundle) {
      'news_release' => filter_var($group->get('field_allow_news_releases')->value, FILTER_VALIDATE_BOOLEAN),
      'perspective' => filter_var($group->get('field_allow_perspectives')->value, FILTER_VALIDATE_BOOLEAN),
      'speeches' => filter_var($group->get('field_allow_speeches')->value, FILTER_VALIDATE_BOOLEAN),
      default => TRUE,
    };

  }
}
