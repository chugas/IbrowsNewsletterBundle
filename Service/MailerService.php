<?php

namespace Ibrows\Bundle\NewsletterBundle\Service;

class MailerService {

  protected $mailer;
  protected $transport;
  protected $encryption;
  protected $container;

  public function __construct($mailer, $transport, $encryption) {
    $this->mailer = $mailer;
    $this->transport = $transport;
    $this->encryption = $encryption;
  }

  public function send($job) {
    $newsletter = $job->getNewsletter();
    $subscriber = $job->getSubscriber();
    $mandant = $newsletter->getMandant();
    $sendSettings = $mandant->getSendSettings();    
    $message = \Swift_Message::newInstance()
            ->setSubject($newsletter->getSubject())
            ->setFrom(array($newsletter->getSenderMail() => $newsletter->getSenderName()))
            ->setReturnPath($newsletter->getReturnMail())
            ->setBody($newsletter->getBody())
            ->setContentType('text/html');

    $name = $subscriber->getFirstname() . ' ' . $subscriber->getLastname();
    $to = array($subscriber->getEmail() => (trim($name) == '' ? $subscriber->getEmail() : $name));
    $message->setTo($to);

    $this->transport->setUsername($sendSettings->getUsername());
    $password = $this->encryption->decrypt($sendSettings->getPassword(), $mandant->getSalt());
    $this->transport->setPassword($password);

    $this->transport->setHost($sendSettings->getHost());
    $this->transport->setPort($sendSettings->getPort());
    $this->transport->setEncryption($sendSettings->getEncryption());
    $this->transport->setAuthMode($sendSettings->getAuthMode());

    $this->mailer->newInstance($this->transport)->send($message);
  }

}
