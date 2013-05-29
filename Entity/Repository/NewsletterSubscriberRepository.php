<?php

namespace Ibrows\Bundle\NewsletterBundle\Entity\Repository;

use Ibrows\Bundle\NewsletterBundle\Entity\NewsletterSubscriber;
use Doctrine\ORM\EntityRepository;

class NewsletterSubscriberRepository extends EntityRepository {

  public function scheduledSend($limit) {
    $em = $this->getEntityManager();

    $consulta = $em->createQuery('
        SELECT ns, n, s 
        FROM SuccessNewsletterBundle:Newsletter\NewsletterSubscriber ns JOIN ns.newsletter n JOIN ns.subscriber s 
        WHERE ns.status = :status AND n.starttime <= CURRENT_TIMESTAMP() 
    ');

    $consulta->setParameter('status', NewsletterSubscriber::STATUS_READY);
    $consulta->setMaxResults($limit);
    
    return $consulta->getResult();
  }

}