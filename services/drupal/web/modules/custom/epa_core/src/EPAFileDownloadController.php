<?php

namespace Drupal\epa_core;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extend system FileDownloadController.
 */
class EPAFileDownloadController extends FileDownloadController {

  /**
   * EPAFileDownload service.
   *
   * @var \Drupal\epa_core\EPAFileDownload
   */
  protected $epaFileDownload;

  /**
   * EPAFileDownloadController constructor.
   *
   * @param \Drupal\epa_core\EPAFileDownload $epa_file_download
   *   The epaFileDownload service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(EPAFileDownload $epa_file_download, StreamWrapperManagerInterface $stream_wrapper_manager = NULL) {
    parent::__construct($stream_wrapper_manager);
    $this->epaFileDownload = $epa_file_download;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('epa_core.private_file_download'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * If current user is anonymous, make a decision about whether to
   * serve file based on the associated media.
   */
  public function download(Request $request, $scheme = 'private') {
    $this->epaFileDownload->privateFileCheck($request, $scheme);
    $response = parent::download($request, $scheme);
    return $response;
  }

}
