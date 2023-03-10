<?php

namespace Drupal\ds\Controller;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Route controller fields.
 */
class FieldController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\ds\Controller\FieldController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(StorageInterface $storage, ModuleHandlerInterface $module_handler) {
    $this->storage = $storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('module_handler')
    );
  }

  /**
   * Builds a list of fields.
   */
  public function fieldList() {
    $custom_fields = $this->storage->listAll('ds.field.');
    if (!empty($custom_fields)) {

      $rows = [];
      foreach ($custom_fields as $config) {
        $field_value = $this->config($config)->get();
        $row = [];
        $row[] = [
          'data' => [
            '#plain_text' => $field_value['label'],
          ],
        ];
        $row[] = $field_value['type_label'] ?? $this->t('Unknown');
        $row[] = $field_value['id'];
        $row[] = ucwords(str_replace('_', ' ', implode(', ', $field_value['entities'])));

        $operations = [];
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'url' => new Url('ds.manage_field', ['field_key' => $field_value['id']]),
        ];
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => new Url('ds.delete_field', ['field_key' => $field_value['id']]),
        ];

        $this->moduleHandler->alter('ds_field_operations', $operations, $field_value);

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#subtype' => 'ds',
            '#links' => $operations,
          ],
        ];

        $rows[] = $row;
      }

      $build = [
        '#theme' => 'table',
        '#header' => [
          'Label',
          'Type',
          'Machine name',
          'Entities',
          'Operations',
        ],
        '#rows' => $rows,
      ];

    }
    else {
      $build = [
        '#markup' => $this->t('No custom fields have been defined.'),
      ];
    }

    return $build;
  }

  /**
   * Redirect to the correct manage callback.
   */
  public function manageRedirect($field_key) {
    $config = $this->config('ds.field.' . $field_key);
    if ($field = $config->get()) {
      $url = new Url('ds.manage_' . $field['type'] . '_field', ['field_key' => $field_key]);
      if ($url->toString()) {
        return new RedirectResponse($url->toString());
      }
    }

    $this->messenger()->addMessage($this->t('Field not found'));
    $url = new Url('ds.fields_list');
    return new RedirectResponse($url->toString());
  }

}
