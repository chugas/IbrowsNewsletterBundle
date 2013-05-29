<?php

namespace Ibrows\Bundle\NewsletterBundle\Model\Newsletter;

abstract class NewsletterSubscriberManager {

  protected $class;

  public function __construct($class) {
    $this->class = $class;
  }

  public function create() {
    return new $this->class();
  }

}
