<?php
namespace controllers;
use \bloc\View;


/**
 * Overview covers 'pages' that have a pretty broad and specific agenda.
 */

  class Overview extends Manage
  {
    public function GETpolicy()
    {
      $view = new View($this->partials->layout);
      $view->content   = 'views/pages/policy.html';
      return $view->render($this());
    }
    
    public function GETtciaf()
    {
      /*
        TODO show staff
      */
      $view = new View($this->partials->layout);
      $view->content   = 'views/pages/about.html';
      return $view->render($this());
    }
    
  }