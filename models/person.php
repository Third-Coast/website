<?php
namespace models;

/**
 * Person
 */

class Person extends Model
{  
  public $form = 'vertex';
  
  static public $fixture = [
    'vertex' => [
      'abstract' => [
        [ 
          'CDATA'  => '',
          '@' => ['content' => 'bio']
        ]
      ]
    ]
  ];
  
  protected $references = [
    'has' => [
      'extra'    => ['article'],
    ],
    'acts'    => [
      'producer' => ['feature', 'broadcast'],
      'host'     => ['happening', 'competition'],
      'judge'    => ['competition'],
      'sponsor'  => ['happening', 'competition'],
      'curator'  => ['collection'],
    ]
  ];
    
  public function authenticate($password)
  {
    if (! password_verify($password, $this->context->getAttribute('hash'))) {
      throw new \InvalidArgumentException("Might I ask you to try once more?", 1);
    }
    return $this->context;
  }
  
  public function authorize()
  {
    /*
      TODO need a system to check for role of staff or contributor.
    */
  }
  
  public function setIdAttribute(\DOMElement $context, $value)
  {
    
    if (empty($value)) {
      $value = 'p-' . preg_replace('/[^a-z0-9]/i', '', static::$fixture['vertex']['@']['title']);
    }
    
    
    
    if (empty($value)) {
      $this->errors[] = "Name Invalid, either doesn't exist, or is not unique enough.";
      throw new \RuntimeException($message, 1);
    }

    $context->setAttribute('id', $value);
    
  }
  
  public function getHash($string)
  {
    return password_hash($string, PASSWORD_DEFAULT);
  }
  
  public function getBio(\DOMElement $context)
  {
    $this->parseText($context);
    return isset($this->bio) ? $this->bio : null;
  }
  
  public function getPhoto(\DOMElement $context)
  {
    if ($photo = $this->media['image']->current()) {
      return $photo;
    }
  }
  
  public function getFeatures(\DOMElement $context)
  {
    $features = $context->find("edge[@type='producer']");
    if ($features->count() > 0) {
      return $features->map(function($collection) {
        return ['feature' => new Feature($collection['@vertex'])];
      });
    }
  }
  
}