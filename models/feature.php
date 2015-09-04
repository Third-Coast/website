<?php

namespace models;

/**
  * Feature
  *
  */
  class Feature extends Model
  {    
    static public $fixture = [
      'vertex' => [
        'location' => [
          'CDATA' => ''
        ],
        'premier' => [
          'CDATA' => '',
          '@' => [
            'date' => null
          ]
        ],
        'spectra' => [
          '@' => ['F'=>50,'S'=>50,'M'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50]
        ]
      ]
    ];
    
    protected $references = [
      'has' => [
        'producer' => ['person'],
        'extra'    => ['article', 'feature'],
        'award'    => ['competition'],
      ],
      'acts'    => [
        'item'        => ['collection', 'happening'],
        'participant' => ['competition'],
      ]
    ];
    
    protected $edges = [
      'producer'    => ['person'],
      'host'        => ['person'],
      'extra'       => ['article', 'feature'],
      'award'       => ['competition'],
      'item'        => ['collection', 'happening'],
      'participant' => ['competition'],
    ];
    
    public function getSpectra(\DOMElement $context)
    {
      $spectra = $this::$fixture['vertex']['spectra']['@'];
      
      if ($spectrum = $context->getFirst('spectra')) {
        foreach ($spectrum->attributes as $attr) {
          $spectra[$attr->name] = $attr->value;
        }
      }
      
      return Graph::instance()->query('graph/config')->find('/spectra')->map(function($item) use($spectra) {
        return ['item' => $item, 'title' => $item->nodeValue, 'value' => $spectra[$item['@id']]];
      });
    }
    
    public function getGradient(\DOMElement $context)
    {
      $color = '-webkit-linear-gradient(left, %s)';
      $count = 0;
      
      foreach ($this->getSpectra($context) as $spectra) {
        $h = round($count++ * 255);
        $s = round((abs(50 - $spectra['value']) / 100) * 200) . '%';
        $l = round(((abs(100 - $spectra['value']) / 100) * 50) + 40) . '%';
        $colors[] = sprintf('hsla(%s, %s, %s, 0.35)', $h, $s, $l);
      }

      return sprintf($color, implode(',', $colors));
    }
    
    public function setAbstract(\DOMElement $context, array $abstract)
    {
      if ($abstract['@']['content'] == 'description' && empty($abstract['CDATA'])) {
        $context->setAttribute('content', 'description');
        throw new \UnexpectedValueException("Please add a description", 400);
      }
      
      return parent::setAbstract($context, $abstract);
    }
        
    public function getAward(\DOMElement $context)
    {
      $award = $context->find("edge[@type='award']");

      if ($award->count() > 0) {
        $edge = $award->pick(0);
        return new \bloc\types\Dictionary(['title' => $edge->nodeValue, 'competition' => new Competition($edge['@vertex'])]);
      }
    }
    
    public function getExtra(\DOMElement $context)
    {
      return isset($this->extra) ? $this->extra : null;
    }
    
    public function getBackground()
    {
      if ($image = $this->media['image'][0]) {
        
        if ($im = @imagecreatefromjpeg('http://s3.amazonaws.com/'.$image->url)) {
          imagefilter($im, IMG_FILTER_PIXELATE, 10);
          imagefilter($im, IMG_FILTER_CONTRAST, 50);
          imagefilter($im, IMG_FILTER_COLORIZE, 255, 255, 255, 100);
          imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);
          ob_start();
          imagejpeg($im, null, 100);
          $i = ob_get_clean();
          $background = "data:image/jpg;base64," . base64_encode($i);
        } else {
          return null;
        }
        imagedestroy($im);
        return $background;
      }
        
    }
    
    public function getProducers(\DOMElement $context)
    {
      return $context->find("edge[@type='producer']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex'])];
      });
    }
    
    public function getPlaylists(\DOMElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($collection) {
        return ['collection' => new Collection($collection['@vertex'])];
      });
    }
    
    public function getExtras(\DOMElement $context)
    {
      return $context->find("edge[@type='extra']")->map(function($extra) {
        return ['article' => new Article($extra['@vertex'])];
      });
    }
    
    public function getCompetitions(\DomElement $context)
    {
      return $context->find("edge[@type='participant']")->map(function($extra) {
        return ['competition' => new Competition($extra['@vertex'])];
      });
    }
    
    public function getFestivals(\DomElement $context)
    {
      return $context->find("edge[@type='item']")->map(function($extra) {
        return ['happening' => new Happening($extra['@vertex'])];
      });
    }
   
    public function getRecommended(\DOMElement $context)
    {
      $correlation = \controllers\Task::pearson($context['@id'])->best;
      arsort($correlation);

      return (new \bloc\types\Dictionary(array_keys(array_slice($correlation, 0, 5, true))))->map(function($id) {
       return ['item' => Graph::factory(Graph::ID($id))];
      });
    } 
  }