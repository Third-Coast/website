<?php
namespace controllers;

use \bloc\application;
use \bloc\view;
use \bloc\view\renderer;
use \bloc\types\string;
use \bloc\types\xml;
use \bloc\types\dictionary;

use \models\graph;

/**
 * Third Coast International Audio Festival Defaults
 */

class Manage extends \bloc\controller
{

  protected $partials;
  
  public function __construct($request)
  {
    $this->partials = new \StdClass();

    View::addRenderer('before', Renderer::addPartials($this));
    View::addRenderer('after', Renderer::HTML());
        
    $this->authenticated = (isset($_SESSION) && array_key_exists('user', $_SESSION));

		$this->year = date('Y');
    $this->title = "Third Coast";
    
    $this->supporters = Graph::group('organization')->find("vertex[edge[@type='sponsor' and @vertex='TCIAF']]");
    
    if ($this->authenticated) {

      $this->user = Application::instance()->session('TCIAF')['user'];
      $this->tasks = (new Dictionary(['person', 'feature', 'broadcast', 'article', 'competition', 'organization', 'event', 'conference', 'festival']))->map(function($task) {
        return ['name' => $task];
      });
      $this->partials->helper = 'views/partials/admin.html';
    }
  }
  
  public function GETindex()
  {
    return (new View($this->partials->layout))->render($this());
  }
  
  public function GETlogin($redirect = '/', $username = null, $message = null)
  {
    if ($this->authenticated) \bloc\router::redirect($redirect);
    
    Application::instance()->getExchange('response')->addHeader("HTTP/1.0 401 Unauthorized");

    $view = new view('views/layout.html');
    $view->content = 'views/forms/credentials.html';

    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ip2long(getenv('REMOTE_ADDR')) + ip2long(getenv('SERVER_ADDR'));
    $this->input = new \bloc\types\Dictionary([
      'token'    => base_convert($key, 10, date('G')+11),
      'message'  => $message ?: 'Login',
      'username' => $username,
      'password' => null, 
      'redirect' => $redirect,
      'tokens'   => [
        'username' => String::rotate('username', $token),
        'password' => String::rotate('password', $token),
        'redirect' => String::rotate('redirect', $token),
      ]
    ]);
      
    return $view->render($this());
  }
  
  public function POSTLogin($request, $key)
  {
    $token = date('zG') + 1 + strlen(getenv('HTTP_USER_AGENT'));
    $key = ($key === base_convert((ip2long($_SERVER['REMOTE_ADDR']) + ip2long($_SERVER['SERVER_ADDR'])), 10, date('G')+11));
     
    $username = $request->post(String::rotate('username', $token));
    $password = $request->post(String::rotate('password', $token));
    $redirect = $request->post(String::rotate('redirect', $token));
    
    if ($key) {
      try {
        $user = (new \models\person('p-' . preg_replace('/\W/', '', $username)))->authenticate($password);
        Application::instance()->session('TCIAF', ['user' =>  $user->getAttribute('title')]);
        \bloc\router::redirect($redirect);
      } catch (\InvalidArgumentException $e) {
        $message = sprintf($e->getMessage(), $username);
      }
    } else {
      $message = "This form has expired - it can happen.. try again!";
    }
    
    return $this->GETLogin($redirect, $username, $message);
  }
  
  protected function GETedge($model, $direction, $id = null)
  {
    $view = new view('views/layout.html');
    $view->content = "views/forms/edge-{$direction}.html";
    $this->direction = $direction;
    $this->model     = $model;
    $this->vertex    = Graph::ID($id);
    $this->groups    = Graph::GROUPS($model);
    $this->types     = Graph::RELATIONSHIPS();
    
    return $view->render($this());
  }
  
  protected function POSTedge($request, $direction)
  {
        
    $view = new view('views/layout.html');
    $view->content = "views/forms/partials/edge-{$direction}.html";

    $this->vertex = Graph::factory(Graph::ID($_POST['id']));
    $this->edge   = Graph::EDGE(null, $_POST['type'], $_POST['caption']);
    
    $this->process = 'add';
    $this->checked = 'checked';
    
    
    $this->index = time() * -1;
    
    return $view->render($this());
  }
  
  protected function GETMedia($vertex, $type, $index = null)
  {
    $view = new view('views/layout.html');
    
    $this->media = \models\Media::COLLECT(Graph::ID($vertex)['media'], $type);
    $index -= 1;
    
    if ($index >= 0) {
      $view->content = 'views/forms/partials/media.html';
      foreach ($this->media[$index] as $key => $value) {
        $this->{$key} = $value;
      }
    } else {
      $view->content = 'views/forms/media.html';
    }
    return $view->render($this());
  }
  
  // Create a new vertex model from scratch
  // output: HTML Form
  protected function GETcreate($model)
  {
    $this->item       = Graph::factory($model);
    $this->action     = "Create New {$model}";
    $this->references = null;
    $this->edges      = null;
    
    
    $view = new view('views/layout.html');    
    $view->content = sprintf("views/forms/%s.html", $this->item->getForm());

    return $view->render($this()); 
  }
  
  // Fetch a vertex and create a model.
  // output: HTML Form
  protected function GETedit($id)
  {
    $this->item   = Graph::factory(Graph::ID($id));
    $this->action = "Edit {$this->item->get_model()}";
    $this->edges  = $this->item->edge->map(function($edge) {
      return [ 'vertex' => Graph::factory(Graph::ID($edge['@vertex'])), 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'keep'];
    });
    
    $this->references = Graph::instance()->query('graph/group/vertex')->find("/edge[@vertex='{$id}']")->map(function($edge) {
      return ['vertex' => Graph::factory($edge->parentNode), 'edge' => $edge, 'index' => $edge->getIndex(), 'process' => 'remove'];
    });

    $view = new view('views/layout.html');
    $view->content = sprintf("views/forms/%s.html", $this->item->getForm());
    
    return $view->render($this());
  }
  
  protected function POSTedit($request, $model, $id = null)
  {
    if ($instance = Graph::factory( (Graph::ID($id) ?: $model), $_POST)) {
      if ($instance->save()) {
        // clear caches
        \models\Search::clear();
        if (isset($_POST['edge'])) {
          $instance->setReferencedEdges($_POST['edge']);
        }
        \bloc\router::redirect("/manage/edit/{$instance['@id']}");
      } else {
        echo $instance->context->write(true);
        \bloc\application::instance()->log($instance->errors);
      }
    } 
    
    
  }
  
  protected function POSTupload($request)
  {
    $name   = preg_replace(['/[^a-zA-Z0-9\-\:\/\_\.]/', '/\.jpeg/'], ['', '.jpg'], $_FILES['upload']['name']);
    $src    = 'data/media/' . $name;
    $mime   = $_FILES['upload']['type'];
    $bucket = 'tciaf-media';
    $type = substr($mime, 0, strpos($mime, '/'));

    if (move_uploaded_file($_FILES['upload']['tmp_name'], PATH . $src)) {
      $view = new view('views/layout.html');
      $view->content = 'views/forms/partials/media.html';
      $client = \Aws\S3\S3Client::factory(['profile' => 'TCIAF']);
      
      try {
        $config = [
          'Bucket' => $bucket,
          'Key'    => $type . '/' . $name,
          'ACL'    => 'public-read',
        ];
        
        if ($type === 'image') {
          $config['Body'] =  file_get_contents("http://{$_SERVER['HTTP_HOST']}/assets/scale/800/{$name}");
        } else {
          $config['SourceFile'] = PATH . $src;
        }
        
        $result = $client->putObject($config);
        
        $media = Graph::instance()->storage->createElement('media', 'A caption');
        $media->setAttribute('src',  "/{$bucket}/{$type}/{$name}");
        $media->setAttribute('name',  $name);
        $media->setAttribute('type', $type);
      
        $model = new \models\Media($media, (time() * -1));
      
        foreach ($model as $key => $value) {
          $this->{$key} = $value;
        }
      
        return $view->render($this());
      } catch (\Exception $e) {
        return $this->GETerror("The file was unable to be uploaded to amazon.", 500);
        exit();
      }
      
      
     
    } else {
      return $this->GETerror("The Server has refused this file", 400);
    }
  }
  
  
}