<?php

/**
 * @file
 * Contains \Drupal\config\Controller\ConfigController
 */

namespace Drupal\config\Controller;

use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for config module routes.
 */
class ConfigController implements ContainerInjectionInterface {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.storage.staging'),
      $container->get('config.manager'),
      new FileDownloadController()
    );
  }

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(StorageInterface $target_storage, StorageInterface $source_storage, ConfigManagerInterface $config_manager, FileDownloadController $file_download_controller) {
    $this->targetStorage = $target_storage;
    $this->sourceStorage = $source_storage;
    $this->configManager = $config_manager;
    $this->fileDownloadController = $file_download_controller;
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    file_unmanaged_delete(file_directory_temp() . '/config.tar.gz');

    $archiver = new ArchiveTar(file_directory_temp() . '/config.tar.gz', 'gz');
    foreach ($this->targetStorage->listAll() as $name) {
      $archiver->addString("$name.yml", Yaml::encode(\Drupal::config($name)->get()));
    }
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
      }
    }

    $request = new Request(array('file' => 'config.tar.gz'));
    return $this->fileDownloadController->download($request, 'temporary');
  }

  /**
   * Shows diff of specificed configuration file.
   *
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   *
   * @return string
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL) {
    if (!isset($collection)) {
      $collection = StorageInterface::DEFAULT_COLLECTION;
    }
    $diff = $this->configManager->diff($this->targetStorage, $this->sourceStorage, $source_name, $target_name, $collection);
    $formatter = new \DrupalDiffFormatter();
    $formatter->show_header = FALSE;

    $build = array();

    $build['#title'] = t('View changes of @config_file', array('@config_file' => $source_name));
    // Add the CSS for the inline diff.
    $build['#attached']['css'][] = drupal_get_path('module', 'system') . '/css/system.diff.css';

    $build['diff'] = array(
      '#type' => 'table',
      '#header' => array(
        array('data' => t('Old'), 'colspan' => '2'),
        array('data' => t('New'), 'colspan' => '2'),
      ),
      '#rows' => $formatter->format($diff),
    );

    $build['back'] = array(
      '#type' => 'link',
      '#attributes' => array(
        'class' => array(
          'dialog-cancel',
        ),
      ),
      '#title' => "Back to 'Synchronize configuration' page.",
      '#href' => 'admin/config/development/configuration',
    );

    return $build;
  }
}
