<?php
/**
 * @file
 * Contains \Drupal\kalastatic\Controller\KalastaticAdmin.
 */

namespace Drupal\kalastatic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Asset;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KalastaticServer extends ControllerBase {
  public function content() {


    // return array(
    //   '#markup' => 'this is working',
    // );

    //$library_discovery = \Drupal::service('library.discovery');
    //$libraries = $this->libraryDiscoveryParser->buildByExtension('css_js_settings');

    // Get the path and split it up into an array. Note that due to our (not so)
    // fancy path processor in KalastaticPathProcessor everything underneath
    // /kalastatic/ is coming in split up with colons. This brings back the
    // 'catch all' functionality of hook_menu that we relied on in D7 to hanlde
    // everything below a given path.
    // $path = \Drupal::request()->getpathInfo();
    // $args = explode('/', $path);
    // $args = explode(':', $args[2]);
    // $path = implode('/', $args);

    //xdebug_break();
    //$path = 'index.html';
    //$needs_headers = FALSE;
    // Check if we're visiting something deeper than the styleguide or prototype
    // home page.
    //if (isset($args[3]) && !empty($args[3])) {
      //$suffix = '';

      // Regex on the last arg to see if we're trying to load a page or a file.
      //$last_arg = end($args);
      //preg_match('/\.[^\.]+$/i', $last_arg, $ext);

      // if (isset($ext[0])) {
      //   // This is a file so we need to add headers for it's mime type before we
      //   // return the file.
      //   $needs_headers = TRUE;
      // }
      // else {
      //   // Assume we're loading the html page.
      //   $suffix = '/' . $path;
      //}

      // if (isset($ext[0]) && $ext[0] == '.html') {
      //   $suffix = '/' . $path;
      // }

      // $build_args = $args;
      // unset($build_args[0]);
      // //unset($build_args[1]);
      // $path = implode('/', $build_args);
    // }
    // else {
    //   // There isn't a requested page, so redirect the user to index.html
    //   // homepage.
    //   $path = Url::fromUri('base:/kalastatic/prototype/index.html');
    //   return new RedirectResponse($path->toString());
    // }
    xdebug_break();
    $build_path = $this->getBuildPath();

    // Get an array of the url args.
    $path = \Drupal::request()->getpathInfo();
    $args = explode('/', $path);

    // Get rid of the empty one and also the 'kalastatic' that will always be
    // there.
    unset($args[0]);
    unset($args[1]);

    // Now we have the path to look for inside the Kalastatic build path.
    $path = implode('/', $args);

    // Regex on the last arg to see if we're trying to load a page or a file.
    $last_arg = end($args);
    preg_match('/\.[^\.]+$/i', $last_arg, $ext);
    $build_path = $this->getBuildPath();
    if (isset($ext[0])) {
      // This is a file so let's build the path to it.
      $file = $build_path . '/' . $path;
    }
    else {
      // This is a path with no file at the end but there might be an index.html
      // to load.
      $file = $build_path . '/' . $path . '/index.html';
    }

    //xdebug_break();

    // Add headers if needed.
    //if ($needs_headers) {
      //$mime_type = file_get_mimetype($file);
      //drupal_add_http_header('Content-Type', $mime_type);
      //TODO: headers and mime types need to be handed in need to be handled
      // in an event subscriber http://drupal.stackexchange.com/questions/198850/replacement-for-drupal-add-http-header
    //}

    if (file_exists($file)) {
      $file_contents = file_get_contents($file);
      // We have a file to serve so let's do it!
      $output = new HtmlResponse($file_contents);
    }
    elseif (file_exists($build_path)){
      // Kalastatic seems to exist but the page that was requested doesn't so
      // let's throw a Drupal 404.
      drupal_set_message(t('The requested page could not be found inside Kalastatic'), 'error');
      throw new NotFoundHttpException();
    }
    else {
      // Kalastatic isn't building to where it's supposed to be so output a nice
      // message.
      $output = $this->kalastaticNotFoundMessage();
    }

    return $output;
  }

  /**
   * Kalastatic isn't building to where it's supposed to be so output a nice
   * message.
   */
  public function kalastaticNotFoundMessage() {
    $link_path = Url::fromRoute('kalastatic.settings');
    $link_text = t('Kalastatic settings page');
    $replacements = array(
      '@path' => '\'' . $this->getBuildPath() . '\'',
      '@link' => $this->l($link_text, $link_path),
    );

    return array(
      '#type' => 'markup',
      '#markup' => '<h2>' . t('Kalastatic build could not be found!') . '</h2><p>' . t("We were looking in @path but it wasn't there. If Kalastatic is living somewhere else you can set the location on the @link.", $replacements) . '</p>',
    );
  }

  /**
   * Throw a 404 and set a message.
   */
  public function pathNotFound() {
    drupal_set_message(t('The requested page could not be found inside Kalastatic'), 'error');
    throw new NotFoundHttpException();
  }

  /**
   * Return the path to the Kalastatic project relative to the site root.
   */
  public function getFilePath() {
    // If the path variable is set then use that, otherwise assume a default
    // inside the current theme.
    $path = \Drupal::config('kalastatic.settings')->get('kalastatic_src_path');
    return $path ? $path : kalastatic_path_to_src_default();
  }

  /**
   * Return the path to the built prototype relative to the site root.
   */
  public function getBuildPath() {
    // If the path variable is set then use that, otherwise assume a default
    // inside the current theme.
    $path = \Drupal::config('kalastatic.settings')->get('kalastatic_build_path');
    return $path ? $path : kalastatic_path_to_build_default();
  }
}