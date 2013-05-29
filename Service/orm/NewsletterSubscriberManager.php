<?php

namespace Ibrows\Bundle\NewsletterBundle\Service\orm;

use Doctrine\Common\Persistence\ObjectRepository;
use Ibrows\Bundle\NewsletterBundle\Model\Newsletter\NewsletterSubscriber;
use Ibrows\Bundle\NewsletterBundle\Model\Newsletter\NewsletterSubscriberManager as BaseNewsletterSubscriberManager;

class NewsletterSubscriberManager extends BaseNewsletterSubscriberManager {

  protected $repository;
  protected $connection;

  public function __construct(ObjectRepository $repository, $connection) {
    $this->repository = $repository;
    $this->connection = $connection;
    parent::__construct($repository->getClassName());
  }

  public function get($id) {
    return $this->repository->find($id);
  }

  public function findBy(array $criteria, array $orderBy = array(), $limit = null, $offset = null) {
    return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
  }

  public function findOneBy(array $criteria) {
    return $this->repository->findOneBy($criteria);
  }

  public function bulkUpdate($newsletter) {
    $sql = "UPDATE `ibrows_newsletter_newsletters_subscribers` 
            SET `status` = '" . NewsletterSubscriber::STATUS_READY . "' 
            WHERE `newsletter_id` = " . $newsletter->getId();

    $this->connection->query($sql);
  }

  public function bulkInsert($newsletter, $subscribers) {
    $r = array();

    $sql_init = "INSERT INTO 
             ibrows_newsletter_newsletters_subscribers 
            (`newsletter_id`, `subscriber_id`, `status`)";

    $batchSize = 4000;
    $i = 1;
    $total = count($subscribers);
    foreach ($subscribers as $subscriber) {
      $d = "(" . $newsletter->getId() . ", " . $subscriber->getId() . ", '" . NewsletterSubscriber::STATUS_ONHOLD . "')";
      array_push($r, $d);
      unset($d);
      
      if (($i % $batchSize) == 0 || $i == $total) {
        $sql = $sql_init . ' VALUES ' . implode(',', $r);
        $this->connection->executeUpdate($sql);
        unset($r);
        unset($sql);
        $r = array();
      }
      $i++;
    }
  }
  
  public function scheduledSend($limit){
    return $this->repository->scheduledSend($limit);
  }

}
