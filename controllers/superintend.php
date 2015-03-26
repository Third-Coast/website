<?php
namespace controllers;
use \bloc\view as View;

/**
 * Third Coast International Audio Festival Defaults
 */

class superintend extends \bloc\controller
{

  protected $partials = [
    'layout' => 'views/layout.html',
  ];
  
      
  public function __construct($request, $access)
  {
    View::addRenderer('before', view\renderer::addPartials($this));
    View::addRenderer('after', view\renderer::HTML());
    
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));
		$this->year  = date('Y');
    $this->title = "Third Coast";
    
    $this->supporters = [
      ['name' => 'The MacArthur Foundation'],
      ['name' => 'The Richard H. Driehaus Foundation'],
      ['name' => 'The Boeing Company'],
      ['name' => 'Individual Donors']
    ];

    
    if ($this->authenticated) {
      $this->partials['helper'] = 'views/admin.html';
    }
  }
  
  
  public function index()
  {
    echo 'hi';
  }
  
  public function login($redirect_url, $post_data)
  {
    $view = new View($this->partials['layout']);
    
    $view->content = 'views/forms/credentials.html';
    
    $data = new \bloc\types\dictionary;
    
    $data->username = array_key_exists('username', $post_data) ? $post_data['username'] : '';
    $data->password = array_key_exists('password', $post_data) ? $post_data['password'] : '';
    
    $users = new \bloc\DOM\Document('data/users', ['validateOnParse' => true]);
    $user = $users->getElementById($data->username);
    if ($user && password_verify($data->password, $user->getAttribute('password'))) {
      session_start();
      $_SESSION['user'] = $data->username;
      header("Location: {$redirect_url}");
      exit();
    }
    
    $data->year = 2015;
    $data->action = $redirect_url;
    $data->title = 'TCIAF';
    $data->password = null;   
    print $view->render($data);
    
  }
  

}