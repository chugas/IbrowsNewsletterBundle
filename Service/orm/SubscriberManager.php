<?php

namespace Ibrows\Bundle\NewsletterBundle\Service\orm;

use Doctrine\Common\Persistence\ObjectRepository;
use Ibrows\Bundle\NewsletterBundle\Model\Subscriber\SubscriberManager as BaseSubscriberManager;

class SubscriberManager extends BaseSubscriberManager {

  protected $repository;

  public function __construct(ObjectRepository $repository) {
    $this->repository = $repository;
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
  
  public function findSubscribers($id, $page, $limit){
    return $this->repository->findSubscribers($id, $page, $limit);
  }
  
  public function getFirstSubscriber($newsletter_id = NULL){
    return $this->repository->getFirstSubscriber($newsletter_id);
  }

}
