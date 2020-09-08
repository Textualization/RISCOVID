<?php
// Copyright (C) 2020 Textualization Software Ltd., distributed under AGPLv3

namespace Textualization\RISCOVID;

class Predicate {
  var $predicate;
  var $args;
  function __construct($pred, $args=array()) {
    $this->predicate = $pred;
    $this->args = $args;
  }
  function __toString() {
    return $this->predicate . '(' . join(",", $this->args) . ')';
  }
}

