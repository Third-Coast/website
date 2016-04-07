<?php

namespace models;

/**
  * Feature
  *
  */
  class Feature extends Vertex
  {
    static public $fixture = [
      'vertex' => [
        'spectra' => [
          '@' => ['F'=>50,'S'=>50,'M'=>50,'R'=>50,'P'=>50,'T'=>50,'A'=>50]
        ]
      ]
    ];

    protected $edges = [
      'producer'    => ['person', 'organization'],
      'presenter'   => ['person'],
      'extra'       => ['article', 'feature'],
      'award'       => ['competition'],
      'item'        => ['collection'],
      'session'     => ['happening'],
      'participant' => ['competition'],
    ];

    public function __construct($id = null, $data = [])
    {
      parent::__construct($id, $data);

      $this->template['upload'] = 'audio-image';
      if ($this->happenings->count() > 0 && $this->presenters->count() > 0) {
        $this->template['digest'] = 'session';
      } else if ($this->context->find('edge[@vertex="TCIAF"]')->count() > 0) {
        $this->template['digest'] = 'broadcast';
      }
    }

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
        // throw new \UnexpectedValueException("Please add a description", 400);
      }
      return parent::setAbstract($context, $abstract);
    }

    public function getAward(\DOMElement $context)
    {
      $award = $context->find("edge[@type='award']");

      if ($award->count() > 0) {
        $edge = $award->pick(0);
        $competition = new Competition($edge['@vertex']);
        $html = "<strong>{$edge->nodeValue}</strong><span>{$competition->title}</span>";
        return new \bloc\types\Dictionary(['title' => $edge->nodeValue, 'competition' => $competition, 'html' => $html]);
      }
    }

    public function getExtra(\DOMElement $context)
    {
      return isset($this->extra) ? $this->extra : null;
    }

    public function getDuration(\DOMElement $context)
    {
      return $this->media['size']['audio'] > 0 ? round($this->media['audio'][0]->mark / 60) : 0;
    }

    public function getImage(\DOMElement $context)
    {
      if ($image = $this->media['image'][0]) {
        // \bloc\application::instance()->log($image['domain']);
        return $image;
      }
    }

    public function getProducers(\DOMElement $context)
    {
      return $context->find("edge[@type='producer' and @vertex!='TCIAF']")->map(function($edge) {
        return ['person' => new Person($edge['@vertex']), 'role' => 'Producer'];
      });
    }

    public function getPresenters(\DOMElement $context)
    {
      $presenters = $context->find("edge[@type='presenter']");
      $count = $presenters->count();
      return $presenters->map(function($edge) use ($count){
        return ['person' => new Person($edge['@vertex']), 'role' => 'Presenter', 'count' => $count];
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

    public function getHappenings(\DomElement $context)
    {
      return $context->find("edge[@type='session']")->map(function($extra) {
        return ['happening' => new Happening($extra['@vertex'])];
      });
    }

    public function getRecommended(\DOMElement $context)
    {
      $correlation = \controllers\Task::pearson($context['@id'])->best;
      arsort($correlation);

      return (new \bloc\types\Dictionary(array_keys(array_slice($correlation, 0, 3, true))))->map(function($id) {
       return ['item' => Graph::FACTORY(Graph::ID($id))];
      });
    }

  }
