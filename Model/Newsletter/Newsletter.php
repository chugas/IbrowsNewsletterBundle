<?php

namespace Ibrows\Bundle\NewsletterBundle\Model\Newsletter;

use Ibrows\Bundle\NewsletterBundle\Model\Block\BlockInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Design\DesignInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Subscriber\SubscriberInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Mandant\MandantInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

abstract class Newsletter implements NewsletterInterface {

  protected $id;
  protected $blocks;
  protected $mandant;
  protected $body;

  /**
   * @var string
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $hash;

  /**
   * @var Collection
   * @Assert\NotNull(groups={"subscriber"})
   */
  protected $newsletters_subscribers;

  /**
   * @var string $subject
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $subject;

  /**
   * @var string $name
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $name;
  
  /**
   * @var DesignInterface $design
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $design;

  /**
   * @var string $senderMail
   * @Assert\Email(groups={"newsletter"})
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $senderMail;

  /**
   * @var string $senderName
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $senderName;

  /**
   * @var string $returnMail
   * @Assert\Email(groups={"newsletter"})
   * @Assert\NotBlank(groups={"newsletter"})
   */
  protected $returnMail;

  /**
   * @var \DateTime $createdAt
   * @Assert\NotNull(groups={"newsletter"})
   */
  protected $createdAt;

  /**
   * @var SendSettingsInterface $sendSettings
   */
  protected $sendSettings;
  
  protected $subscribers;

  public function __construct() {
    $this->newsletters_subscribers = new ArrayCollection();
    $this->subscribers = new ArrayCollection();
    $this->blocks = new ArrayCollection();
    $this->createdAt = new \DateTime();
    $this->hash = $this->generateHash();
  }

  /**
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * @return string
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * @param string $subject
   * @return Newsletter
   */
  public function setSubject($subject) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   * @return Newsletter
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getSenderMail() {
    return $this->senderMail;
  }

  /**
   * @param string $senderMail
   * @return Newsletter
   */
  public function setSenderMail($senderMail) {
    $this->senderMail = $senderMail;
    return $this;
  }

  /**
   * @return string
   */
  public function getSenderName() {
    return $this->senderName;
  }

  /**
   * @param string $senderName
   * @return Newsletter
   */
  public function setSenderName($senderName) {
    $this->senderName = $senderName;
    return $this;
  }

  /**
   * @return string
   */
  public function getReturnMail() {
    return $this->returnMail;
  }

  /**
   * @param type $returnMail
   * @return Newsletter
   */
  public function setReturnMail($returnMail) {
    $this->returnMail = $returnMail;
    return $this;
  }
  
  public function getSubscribers() {
    $collection = $this->getNewslettersSubscribers();
    foreach ($collection as $newsletter_subscriber){
      $this->subscribers[] = $newsletter_subscriber->getSubscriber();
    }
    return $this->subscribers;
  }

  /**
   * @return Collection
   */
  public function getNewslettersSubscribers() {
    return $this->newsletters_subscribers;
  }

  /**
   * @param SubscriberInterface $subscriber
   * @return Newsletter
   */
  public function removeNewsletterSubscriber($newsletter_subscriber) {
    $this->newsletters_subscribers->removeElement($newsletter_subscriber);
    return $this;
  }

  /**
   * @param SubscriberInterface $subscriber
   * @return Newsletter
   */
  public function addNewsletterSubscriber($newsletter_subscriber) {
    $newsletter_subscriber->setNewsletter($this);

    $this->newsletters_subscribers[] = $newsletter_subscriber;
  }

  /**
   * @return MandantInterface
   */
  public function getMandant() {
    return $this->mandant;
  }

  /**
   * @param MandantInterface $mandant
   * @return Newsletter
   */
  public function setMandant(MandantInterface $mandant) {
    $this->mandant = $mandant;
    return $this;
  }

  /**
   * @param \DateTime $dateTime
   * @return Newsletter
   */
  public function setCreatedAt(\DateTime $dateTime) {
    $this->createdAt = $dateTime;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getCreatedAt() {
    return $this->createdAt;
  }
  
  public function getStarttime() {
    return $this->starttime;
  }

  public function setStarttime($starttime) {
    $this->starttime = $starttime;
    return $this;
  } 

  /**
   * @return Collection
   */
  public function getBlocks() {
    return $this->blocks;
  }

  /**
   * @param BlockInterface $block
   * @return Newsletter
   */
  public function addBlock(BlockInterface $block) {
    $block->setNewsletter($this);
    $this->blocks->add($block);
    return $this;
  }

  /**
   * @param BlockInterface $block
   * @return Newsletter
   */
  public function removeBlock(BlockInterface $block) {
    $block->setNewsletter(null);
    $this->blocks->removeElement($block);
    return $this;
  }

  /**
   * @return DesignInterface
   */
  public function getDesign() {
    return $this->design;
  }

  /**
   * @param DesignInterface $design
   * @return Newsletter
   */
  public function setDesign(DesignInterface $design) {
    $this->design = $design;
    return $this;
  }

  protected function generateHash() {
    return sha1(uniqid() . time());
  }

  /**
   * @return NewsletterSendSettings
   */
  public function getSendSettings() {
    return $this->sendSettings;
  }

  /**
   * 
   * @param SendSettingsInterface $sendSettings
   * @return Newsletter
   */
  public function setSendSettings(SendSettingsInterface $sendSettings) {
    $this->sendSettings = $sendSettings;
    return $this;
  }
  
  /**
   * @return string
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * 
   * @param $body
   * @return Newsletter
   */
  public function setBody($body) {
    $this->body = $body;
    return $this;
  }  

}
