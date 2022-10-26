<?php
namespace models\traits;

trait sponsor {
  public function getSponsors(\DOMElement $context)
  {
    return $this->groupByTitle($context, 'sponsor');
  }
}
