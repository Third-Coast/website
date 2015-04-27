<?php

namespace models;
use \bloc\DOM\Document;

/**
  * Token
  *
  */

  class Token
  {
    const DB = 'data/db7';
  
    private $storage = null;

    static public function storage()
    {
      static $instance = null;
    
      if ($instance === null) {
        $instance = new static();
      }
      return $instance->storage;
    }
    
    static public function ID($id)
    {
      if (! $element = Token::storage()->getElementById($id)) {
        throw new \InvalidArgumentException("{$id}... Doesn't ring a bell.", 1);
      }
      return $element;
    }
  
    static public function factory($model_or_element)
    {
      if ($model_or_element instanceof \DOMElement) {
        $model_or_element = $model_or_element->parentNode->getAttribute('type');
      }
      return  NS . __NAMESPACE__ . NS . $model_or_element;
    }
  
    private function __construct()
    {
      $this->storage = new Document(self::DB, ['validateOnParse' => true]);
    }    
  }