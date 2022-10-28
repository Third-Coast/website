<?php
namespace models;

/**
  * Ensemble
  *
  */

  class Collection extends Vertex
  {
    use traits\banner;

    public $_location = 'Feature (where)';
    public $_premier  = 'Date';

    protected $_help = [
      'overview' => 'Collections generally serve as playlists, see associtions below for adding items.',
      'premier' => '(not implemented)',
      'location' => '(not implemented)',
      'edges' => 'Generally the most important association is item, where you can select features to add. Note, when adding a feature, consider using the "item type" field to discuss why you added the feature to that particular playlist—you can use markdown as well. Adding curators will show the names of the poeple involved in the playlist.',
      'extras' => '(not implemented for collections)'
    ];

    static public $fixture = [
      'vertex' => [
        '@' => ['text' => 'about']
      ]
    ];

    protected $edges = [
      'item'    => ['feature', 'broadcast'],
      'curator' => ['person', 'organization'],
      'page'    => ['article', 'happening'],
      'playlist'=> ['competition', 'happening'],
    ];

    public function __construct($id = null, $data =[])
    {
      $this->template['form'] = 'vertex';
      
      parent::__construct($id, $data);
      
      $this->template['digest'] = $this->articles->count() > 0 ? 'collection/award' : 'collection/playlist';

    }


    public function getFeatures(\DOMElement $context)
    {
      $markdown = new \vendor\Parsedown;
      return $context->find("edge[@type='item']")->map(function($edge) use ($markdown){
        $out = ['item' => new Feature($edge['@vertex'])];
        if ($edge->nodeValue) {
          $out['caption'] = strip_tags($markdown->text($edge), '<em><strong>');
        }
        return $out;
      });
    }
    
    public function getPages(\DOMElement $context)
    {
      $pages = $context->find("edge[@type='page']");
      
      return $pages->count() == 0 ? [] : $pages->map(function($p) {
        return ['page' => new Article($p['@vertex'])];
      });
    }

    public function getTriptych(\DOMElement $context)
    {
      return $this->features->limit(1, 3);
    }
    
    public function getSeven(\DOMElement $context)
    {
      return $this->features->limit(1, 15);
    }

    public function getArticles(\DOMElement $context)
    {
      return $context->find("edge[@type='page']")->map(function($edge) {
        return ['article' => new Article($edge['@vertex'])];
      });
    }

    public function getCurators(\DOMElement $context)
    {
      return $context->find("edge[@type='curator']")->map(function($edge) {
        return ['person' => Graph::FACTORY(Graph::ID($edge['@vertex']))];
      });
    }

    public function getSize(\DOMElement $context)
    {
      $out = [
        'length'   => 0,
        'duration' => 0,
      ];

      foreach ($this->features as $feature) {
        $out['length'] += 1;
        $out['duration'] += (int)$feature['item']->duration;
      }

      $out['duration'] = round($out['duration'] / 60, 1);
      return new \bloc\types\Dictionary($out);
    }
    
    public function getContent(\DOMElement $context)
    {
      $content = parent::getContent($context);
      $object    = \bloc\dom\document::ELEM("<div>{$content['about_chop']}</div>");
      // turn H6 elements into disclosure widgets
      $document = $object->ownerDocument;
      foreach ($document->getElementsByTagName('h6') as $mark) {
        //
        $details = $document->createElement('details');
        $mark = $mark->parentNode->replaceChild($details, $mark);
        $summary = $details->appendChild(new \DOMElement('summary'));
        $summary->appendChild($mark);
        $sibling = $details->nextSibling;
        while($sibling && $sibling->nodeName[0] != 'h') {
          $next = $sibling->nextSibling;
          $details->appendChild($sibling);
          $sibling = $next;
        }
      }
    
      $content['about_chop'] = $document->saveXML($object);
      return $content;
    }
  }
