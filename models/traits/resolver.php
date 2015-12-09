<?php
namespace models\traits;
use \models\Graph;

trait resolver {
  protected function identify($identity) {
    $node = Graph::ID($identity);
    return $node;
  }


  protected function initialize() {

    self::$fixture['vertex']['@']['created'] = (new \DateTime())->format('Y-m-d H:i:s');
    $node = Graph::instance()->storage->createElement('vertex', null);
    $this->input(self::$fixture, $node);
    return Graph::group($this->get_model())->pick('.')->appendChild($node);
  }


  public function save()
  {
    $filepath = PATH . Graph::DB . '.xml';
    $this->setUpdatedAttribute($this->context);
    if (empty($this->errors) && Graph::instance()->storage->validate() && is_writable($filepath)) {
      return Graph::instance()->storage->save($filepath);
    } else {
      print_r(is_writable($filepath));
      $this->errors = array_merge(["Did not save"], $this->errors, array_map(function($error) {
        return $error->message;
      }, Graph::instance()->storage->errors()));

      return false;
    }
  }
}