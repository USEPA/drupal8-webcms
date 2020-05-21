<?php

namespace Drupal\epa_node_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\epa_core\Utility\EpaCoreHelper;
use Drupal\node\NodeInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

/**
 * Provides route responses for the archive creation.
 */
class NodeExportController extends ControllerBase {
  use StringTranslationTrait;

  /**
   * The date formatter interface.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The epa core helper service.
   *
   * @var \Drupal\epa_core\Utility\EpaCoreHelper
   */
  protected $epaCoreHelper;

  /**
   * The file system interface.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The http client from Guzzle.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger interface.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor for the ArchiveController class.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter interface.
   * @param \Drupal\epa_core\Utility\EpaCoreHelper $epa_core_helper
   *   The epa core helper service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system interface.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   */
  public function __construct(DateFormatterInterface $date_formatter, EpaCoreHelper $epa_core_helper, FileSystemInterface $file_system, Client $http_client, LoggerInterface $logger) {
    $this->dateFormatter = $date_formatter;
    $this->epaCoreHelper = $epa_core_helper;
    $this->fileSystem = $file_system;
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('epa_core.helper'),
      $container->get('file_system'),
      $container->get('http_client'),
      $container->get('logger.factory')->get('epa_node_export')
    );
  }

  /**
   * Builds the title for the admin node export page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return string
   *   The title string.
   */
  public function getExportAdminPageTitle(NodeInterface $node) {
    return $this->t('Export "@title"', ['@title' => $node->label()]);
  }

  /**
   * Builds the admin node export page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return array
   *   The render array for the admin page.
   */
  public function buildExportAdminPage(NodeInterface $node) {
    return [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $node->label(),
      ],
      'body' => [
        '#markup' => $this->t('Yo'),
      ],
      '#cache' => [
        'contexts' => [
          'user.roles:authenticated',
        ],
      ],
    ];
  }

  /**
   * Builds the response to return to the browser.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The zipped file for download.
   */
  public function createExportFile(NodeInterface $node) {

    // Bail early if the node isn't published.
    if (!$node->isPublished()) {
      $this->logger->notice('Error while exporting node: @title - @id. The page must be published.', ['@title' => $node->label(), '@id' => $node->id()]);
      throw new AccessDeniedHttpException();
    }

    $url = $node->toURL('canonical', [
      'absolute' => TRUE,
      'base_url' => 'http://localhost',
    ])->toString();

    try {
      $response = $this->httpClient->get($url);
      $code = $response->getStatusCode();
      if ($code == 200) {
        $tempnam = $this->fileSystem->tempnam('temporary://', 'epa_archive_');
        $export_uri = $tempnam . '_1';
        $export_dir = $this->fileSystem->realpath($export_uri);

        exec("cd " . dirname($export_dir) . " && wget --execute robots=off --restrict-file-names=windows --no-host-directories --timestamping --convert-links --adjust-extension --directory-prefix=" . basename($export_dir) . " --recursive --level=1 --page-requisites -I /sites,/epafiles,/misc $url", $output, $return);

        // Bail out if we had an error during the wget call.
        if ($return != 0) {
          $this->logger->notice('Error while exporting: @return', ['@return' => $return]);
          throw new NotFoundHttpException();
        }

        $export_uri_filename = $export_uri . '.zip';
        $export_filename = $this->fileSystem->realpath($export_uri_filename);
        $zip = new ZipArchive();
        $res = $zip->open($export_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res === TRUE) {
          $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($export_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
          );

          foreach ($files as $name => $file) {

            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {

              // Get real and relative path for current file.
              $filePath = $file->getRealPath();
              $relativePath = substr($filePath, strlen($export_dir) + 1);

              // Add current file to archive.
              $zip->addFile($filePath, $relativePath);
            }
          }

          // Zip archive will be created only after closing object.
          $zip->close();

          // Deliver file to the browser for download.
          if (file_exists($export_uri_filename)) {

            // Record this download.
            $machine_name = $this->epaCoreHelper->getEntityMachineNameAlias($node);
            $timestamp = $this->dateFormatter->format(time(), 'custom', 'Y-m-d_H-i');
            $headers = [
              'Content-Type' => 'application/zip',
              'Cache-Control' => 'private',
              'Content-Disposition' => 'attachment; filename="' . $machine_name . '_' . $timestamp . '.zip"',
            ];

            return new BinaryFileResponse($export_uri_filename, 200, $headers);
          }
        }
        else {
          $this->logger->notice('Error while exporting node: @title - @id', ['@title' => $node->label(), '@id' => $node->id()]);
        }
      }
      else {
        $this->logger->notice('Could not export page at @url.  Non-200 response code received: @code', ['@url' => $url, '@code' => $response->code]);
      }

    }
    catch (RequestException $e) {
      $this->logger->notice('Could not export page at @url.', ['@url' => $url]);
      watchdog_exception('notice', $e);
    }

    // If no reponse was returned, then something went wrong. Return a 404.
    throw new NotFoundHttpException();
  }

}
