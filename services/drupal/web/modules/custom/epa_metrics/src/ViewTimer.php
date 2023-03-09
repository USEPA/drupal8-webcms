<?php

namespace Drupal\epa_metrics;

use Drupal\views\ViewExecutable;
use Liuggio\StatsdClient\Sender\SocketSender;
use Liuggio\StatsdClient\Service\StatsdService;
use Liuggio\StatsdClient\StatsdClient;

/**
 * Helper utility class to time the execution of views.
 *
 * The public methods correspond to views' views_pre_FOO and views_post_FOO hooks, which
 * serve as useful points to insert instrumentation code.
 */
class ViewTimer {
  private static $timers = [];

  /**
   * Initiate timing for a views phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   * @param string $phase
   *   The phase ('build', 'execute', and so on)
   */
  private static function startPhase(ViewExecutable $view, $phase) {
    $id = $view->id();
    if (!isset(self::$timers[$id])) {
      return;
    }

    self::$timers[$id][$phase] = microtime(TRUE);
  }

  /**
   * Mark the end of execution of a views phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   * @param string $phase
   *   The phase ('build', 'execute', and so on)
   */
  private static function stopPhase(ViewExecutable $view, $phase) {
    $id = $view->id();
    if (!isset(self::$timers[$id][$phase])) {
      return;
    }

    // Since we're only interested in how long a phase took, we just overwrite the start
    // time with the difference.
    self::$timers[$id][$phase] = microtime(TRUE) - self::$timers[$id][$phase];
  }

  /**
   * Initiate timing for a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function start(ViewExecutable $view) {
    self::$timers[$view->id()] = [];
    self::startPhase($view, 'view');
  }

  /**
   * Records all timing information for a view.
   *
   * This records all of the phases and sends them to the CloudWatch daemon.
   */
  public static function stop(ViewExecutable $view) {
    $id = $view->id();

    self::stopPhase($view, 'view');
    $stats = self::$timers[$id];

    $sender = new SocketSender('127.0.0.1', 8125, 'udp');
    $client = new StatsdClient($sender);
    $service = new StatsdService($client);

    // The statsd protocol uses milliseconds, so we have to convert from the seconds that
    // microtime(TRUE) returns.
    foreach ([
      'view' => 'Drupal.Views.' . $id,
      'build' => 'Drupal.ViewsBuild.' . $id,
      'execute' => 'Drupal.ViewsExecute.' . $id,
      'render' => 'Drupal.ViewsRender.' . $id,
    ] as $stage => $timingkey) {
      if (isset($stats[$stage])) {
        $service->timing($timingkey, $stats[$stage] * 1000);
      }
    }
    $service->flush();

    unset(self::$timers[$id]);
  }

  /**
   * Marks the start of views' build phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function startBuild(ViewExecutable $view) {
    self::startPhase($view, 'build');
  }

  /**
   * Marks the end of views' build phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function stopBuild(ViewExecutable $view) {
    self::stopPhase($view, 'build');
  }

  /**
   * Marks the start of views' execute phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function startExecute(ViewExecutable $view) {
    self::startPhase($view, 'execute');
  }

  /**
   * Marks the end of views' execute phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function stopExecute(ViewExecutable $view) {
    self::stopPhase($view, 'execute');
  }

  /**
   * Marks the start of views' render phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function startRender(ViewExecutable $view) {
    self::startPhase($view, 'render');
  }

  /**
   * Marks the end of views' render phase.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The currently-executing view.
   */
  public static function stopRender(ViewExecutable $view) {
    self::stopPhase($view, 'render');
  }

}
