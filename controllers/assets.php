<?php
namespace controllers;
use \bloc\view as view;

/**
 * Explore Represents the user's interest.
 */

class Assets extends Manage
{

  public function GETimage($file = null)
  {
    View::addRenderer('preflight', function ($view) {
      header('Content-Type: image/svg+xml; charset=utf-8');
    });
    $view = new View('views/images/pidgey.svg');
    $this->brown = '#FFF' ?: '#88746A';
    $this->red = '#FF0000' ?: '#EE3124';
    $this->blue = '#B3DDF2' ?: '#5B9B98';
    return $view->render($this());
  }
  
 
}