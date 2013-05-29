<?php

namespace Ibrows\Bundle\NewsletterBundle\Controller;

use Ibrows\Bundle\NewsletterBundle\Model\Log\LogInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Job\JobInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/statistic")
 */
class StatisticController extends AbstractHashMandantController {

  const TRANSPARENT_GIF = 'R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

  /**
   * @Route("/log/read/{mandantHash}/{newsletterHash}/{subscriberHash}", name="ibrows_newsletter_statistic_log_read")
   */
  public function logreadAction($mandantHash, $newsletterHash, $subscriberHash) {
    $this->setMandantNameByHash($mandantHash);

    $newsletter = $this->getNewsletterByHash($newsletterHash);
    $subscriber = $this->getSubscriberByHash($newsletter, $subscriberHash);

    // if no context is set, its live --> log
    if (!$this->getRequest()->query->get('context')) {
      $this->addNewsletterReadLog($newsletter, $subscriber, "Newsletter read: logged by " . __METHOD__);
    }

    return new Response(base64_decode(self::TRANSPARENT_GIF), 200, array(
                'Content-Type' => 'image/gif'
            ));
  }

  /**
   * @Route("/show/{newsletterId}", name="ibrows_newsletter_statistic_show")
   */
  public function showAction($newsletterId) {
    $newsletter = $this->getNewsletterById($newsletterId);

    $objectManager = $this->getObjectManager();
    $jobs = $objectManager->getRepository($this->getClassManager()->getModel('newsletterSubscriber'))->findBy(
            array(
        'newsletter' => $newsletter->getId()
            ), array(
        'status' => 'ASC'
            )
    );

    $jobPie = array();
    foreach ($jobs as $job) {
      $status = $job->getStatus();
      if (!isset($jobPie[$status])) {
        $jobPie[$status] = 0;
      }
      $jobPie[$status]++;
    }

    $jobStati = array_keys($jobPie);

    $jobsSortedByCompleted = $jobs;
    usort($jobsSortedByCompleted, function($a, $b) {
              $dateA = $a->getCompleted() ? : new \DateTime('now - 15 days');
              $dateB = $b->getCompleted() ? : new \DateTime('now - 15 days');
              return $dateA > $dateB;
            });

    $jobLine = array();
    $jobWalkLine = array();
    foreach ($jobsSortedByCompleted as $job) {

      $dateTime = $job->getCompleted() ? : new \DateTime('now - 15 days');
      $date = $dateTime->format('d.m.Y H:i:s');

      foreach ($jobStati as $jobStatus) {
        if (!isset($jobWalkLine[$jobStatus])) {
          $jobWalkLine[$jobStatus] = 0;
        }

        if (!isset($jobLine[$date])) {
          $jobLine[$date] = array();
        }

        if ($job->getStatus() == $jobStatus) {
          $jobLine[$date][$jobStatus] = ++$jobWalkLine[$jobStatus];
        } else {
          $jobLine[$date][$jobStatus] = $jobWalkLine[$jobStatus];
        }
      }
    }

    return $this->render($this->getTemplateManager()->getStatistic('show'), array(
                'newsletter' => $newsletter,
                'jobPie' => $jobPie,
                'jobLine' => $jobLine
            ));
  }

}