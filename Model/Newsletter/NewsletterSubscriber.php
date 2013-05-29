<?php

namespace Ibrows\Bundle\NewsletterBundle\Model\Newsletter;

abstract class NewsletterSubscriber {

  const STATUS_ONHOLD     = 'onhold';
  const STATUS_READY      = 'ready';
  const STATUS_WORKING    = 'working';
  const STATUS_COMPLETED  = 'completed';
  const STATUS_ERROR      = 'error';

  protected $body;  
  protected $error;
  protected $status;
  protected $completed;  

  public function __construct() {
    $this->status = self::STATUS_ONHOLD;
  }

  public function getError() {
    return $this->error;
  }

  public function setError($error) {
    $this->error = $error;
    return $this;
  }

  public function getStatus() {
    return $this->status;
  }

  public function setStatus($status) {
    $this->status = $status;
    return $this;
  }

  public function getCompleted() {
    return $this->completed;
  }

  public function setCompleted(\DateTime $completed) {
    $this->completed = $completed;
    return $this;
  }
  
  public function getBody() {
    return $this->body;
  }

  public function setBody($body) {
    $this->body = $body;
    return $this;
  }

}
