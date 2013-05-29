<?php

namespace Ibrows\Bundle\NewsletterBundle\Controller;

use Ibrows\Bundle\NewsletterBundle\Model\Newsletter\NewsletterSubscriber;
use Ibrows\Bundle\NewsletterBundle\Model\Job\MailJob;
use Ibrows\Bundle\NewsletterBundle\Model\Newsletter\NewsletterInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Block\BlockInterface;
use Ibrows\Bundle\NewsletterBundle\Model\Subscriber\SubscriberGenderTitleInterface;
use Ibrows\Bundle\NewsletterBundle\Annotation\Wizard\Annotation as WizardAction;
use Ibrows\Bundle\NewsletterBundle\Annotation\Wizard\AnnotationHandler as WizardActionHandler;
use Doctrine\Common\Collections\Collection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NewsletterController extends AbstractController {

  /**
   * @Route("/", name="ibrows_newsletter_index")
   */
  public function indexAction() {
    $this->setNewsletter(null);

    return $this->render($this->getTemplateManager()->getNewsletter('index'), array(
            ));
  }

  /**
   * @Route("/list", name="ibrows_newsletter_list")
   */
  public function listAction() {
    $this->setNewsletter(null);

    return $this->render($this->getTemplateManager()->getNewsletter('list'), array(
                'newsletters' => $this->getMandant()->getNewsletters()
            ));
  }

  /**
   * @Route("/edit/redirection/{id}", name="ibrows_newsletter_edit_redirection")
   */
  public function editredirectionAction($id) {
    $newsletter = $this->getNewsletterById($id);
    $this->setNewsletter($newsletter);

    return $this->redirect($this->generateUrl('ibrows_newsletter_meta'));
  }

  /**
   * @Route("/create", name="ibrows_newsletter_create")
   */
  public function createrediractionAction() {
    $this->setNewsletter(null);

    return $this->redirect($this->generateUrl('ibrows_newsletter_edit'));
  }

  /**
   * @Route("/meta", name="ibrows_newsletter_meta")
   * @WizardAction(name="meta", number=1, validationMethod="metaValidation")
   */
  public function metaAction() {
    $newsletter = $this->getNewsletter();
    if ($newsletter === null) {
      $newsletter = $this->getNewsletterManager()->create();
    }

    $formtype = $this->getClassManager()->getForm('newsletter');
    $designClass = $this->getClassManager()->getModel('design');
    $form = $this->createForm(new $formtype($this->getMandantName(), $designClass), $newsletter);

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) {
        $this->setNewsletter($newsletter);
        return $this->redirect($this->getWizardActionAnnotationHandler()->getNextStepUrl());
      }
    }

    return $this->render($this->getTemplateManager()->getNewsletter('create'), array(
                'newsletter' => $newsletter,
                'form' => $form->createView(),
                'wizard' => $this->getWizardActionAnnotationHandler(),
            ));
  }

  public function metaValidation(WizardActionHandler $handler) {
    
  }

  /**
   * @Route("/edit", name="ibrows_newsletter_edit")
   * @WizardAction(name="edit", number=2, validationMethod="editValidation")
   */
  public function editAction() {
    if (($response = $this->getWizardActionValidation()) instanceof Response) {
      return $response;
    }

    $newsletter = $this->getNewsletter();

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $blockParameters = array();

      $blockPostArray = $request->request->get('block');
      if (!is_array($blockPostArray)) {
        $blockPostArray = array();
      }

      $blockFileArray = $request->files->get('block');
      if (!is_array($blockFileArray)) {
        $blockFileArray = array();
      }

      foreach ($blockPostArray as $blockId => $content) {
        $blockParameters[$blockId] = $content;
      }

      foreach ($blockFileArray as $blockId => $file) {
        $blockParameters[$blockId] = $file;
      }

      $this->updateBlocksRecursive($newsletter->getBlocks(), $blockParameters);

      // clone newsletter blocks?
      $newsletterCloneId = $request->request->get('clone');
      if ($newsletterCloneId) {
        $cloneNewsletter = $this->getNewsletterById($newsletterCloneId);
        if ($cloneNewsletter) {
          $this->cloneNewsletterBlocks($newsletter, $cloneNewsletter);
          $this->setNewsletter($newsletter);
        }
      }
      
      /*****************************************************************************************/
      /*TEMPORAL PATCH TO SAVE BODY OF NEWSLETTER IN NEWSLETTER ENTITY - TO IMPROVE PERFORMANCE*/
      /*THE SUBSCRIBER ENTITY IS ANYTHING ONLY TO CALL renderNewsletter METHOD*/
      $mandant = $this->getMandant();
      $bridge = $this->getRendererBridge();
      $rendererManager = $this->getRendererManager();
      $subscriber = $this->getSubscriberManager()->getFirstSubscriber();
      $body = $rendererManager->renderNewsletter($mandant->getRendererName(), $bridge, $newsletter, $mandant, $subscriber);
      $newsletter->setBody($body);
      $this->setNewsletter($newsletter);
      /*****************************************************************************************/      
      
      $this->setNewsletter($newsletter);

      if ($request->request->get('continue')) {
        return $this->redirect($this->getWizardActionAnnotationHandler()->getNextStepUrl());
      }
    }

    return $this->render($this->getTemplateManager()->getNewsletter('edit'), array(
                'blockProviderManager' => $this->getBlockProviderManager(),
                'newsletter' => $newsletter,
                'newsletters' => $this->getMandant()->getNewsletters(),
                'wizard' => $this->getWizardActionAnnotationHandler(),
            ));
  }

  public function editValidation(WizardActionHandler $handler) {
    if (is_null($this->getNewsletter())) {
      return $this->redirect($handler->getStepUrl($handler->getLastValidAnnotation()));
    }
  }

  /**
   * @Route("/subscriber", name="ibrows_newsletter_subscriber")
   * @WizardAction(name="subscriber", number=3, validationMethod="subscriberValidation")
   */
  public function subscriberAction() {
    if (($response = $this->getWizardActionValidation()) instanceof Response) {
      return $response;
    }

    $newsletter = $this->getNewsletter();
   
    $formtype = $this->getClassManager()->getForm('subscriber');
    $subscriberClass = $this->getClassManager()->getModel('subscriber');
    $form = $this->createForm(new $formtype($this->getMandantName(), $subscriberClass), $newsletter);

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) {
        $this->setNewsletter($newsletter);
        /**************************************************/
        // BULK INSERT TO IMPROVE PERFORMANCE
        // TEMPORAL PATCH ONLY FOR MYSQL
        $groups = $newsletter->getGroups();
        
        foreach ($groups as $group){
          $page = 1;
          do {
            $subscribers = $this->getSubscriberManager()->findSubscribers($group->getId(), $page, 5000);
            $continue = !empty($subscribers);
            $this->getNewsletterSubscriberManager()->bulkInsert($newsletter, $subscribers);
            
            unset($subscribers);
            
            $page++;
          } while($continue);

        }
        /**************************************************/
        
        return $this->redirect($this->getWizardActionAnnotationHandler()->getNextStepUrl());
      }
    }

    return $this->render($this->getTemplateManager()->getNewsletter('subscriber'), array(
                'newsletter' => $this->getNewsletter(),
                'form' => $form->createView(),
                'wizard' => $this->getWizardActionAnnotationHandler(),
            ));
  }

  public function subscriberValidation(WizardActionHandler $handler) {
    if (is_null($this->getNewsletter())) {
      return $this->redirect($handler->getStepUrl($handler->getLastValidAnnotation()));
    }
  }

  /**
   * @Route("/settings", name="ibrows_newsletter_settings")
   * @WizardAction(name="settings", number=4, validationMethod="settingsValidation")
   */
  public function settingsAction() {
    if (($response = $this->getWizardActionValidation()) instanceof Response) {
      return $response;
    }

    $formtype = $this->getClassManager()->getForm('newslettersettings');
    $newsletter = $this->getNewsletter();

    $form = $this->createForm(new $formtype(), $newsletter);

    $request = $this->getRequest();
    if ($request->getMethod() == 'POST') {
      $form->bind($request);

      if ($form->isValid()) {
        $this->setNewsletter($newsletter);
        return $this->redirect($this->getWizardActionAnnotationHandler()->getNextStepUrl());
      }
    }

    return $this->render($this->getTemplateManager()->getNewsletter('settings'), array(
                'newsletter' => $this->getNewsletter(),
                'form' => $form->createView(),
                'wizard' => $this->getWizardActionAnnotationHandler(),
            ));
  }

  public function settingsValidation(WizardActionHandler $handler) {
    $newsletter = $this->getNewsletter();

    if (is_null($newsletter)) {
      return $this->redirect($handler->getStepUrl($handler->getLastValidAnnotation()));
    }

    if (is_null($this->getSubscriberManager()->getFirstSubscriber($newsletter->getId()))) {
      return $this->redirect($this->generateUrl('ibrows_newsletter_subscriber'));
    }
  }

  /**
   * @Route("/summary", name="ibrows_newsletter_summary")
   * @WizardAction(name="summary", number=5, validationMethod="summaryValidation")
   */
  public function summaryAction() {
    if (($response = $this->getWizardActionValidation()) instanceof Response) {
      return $response;
    }

    $newsletter = $this->getNewsletter();

    $formtypeClassName = $this->getClassManager()->getForm('testmail');
    $formtype = new $formtypeClassName(
                    $this->getUser()->getEmail()
    );

    $testmailform = $this->createForm($formtype);

    $request = $this->getRequest();
    $error = '';
    if ($request->getMethod() == 'POST' && $request->request->get('testmail')) {
      $testmailform->bind($request);

      if ($testmailform->isValid()) {
        $tomail = $testmailform->get('email')->getData();        
        $mailjobClass = $this->getClassManager()->getModel('newsletterSubscriber');
        $subscriberClass = $this->getClassManager()->getModel('subscriber');
        
        $subscriber = new $subscriberClass();
        $subscriber->setEmail($tomail);

        $mailjob = new $mailjobClass();
        $mailjob->setNewsletter($newsletter);
        $mailjob->setSubscriber($subscriber);        

        try {
          $this->send($mailjob);
        } catch (\Swift_SwiftException $e) {
          $message = $e->getMessage();
          if ($message) {
            $this->get('session')->getFlashBag()->add('ibrows_newsletter_error', 'newsletter.error.mail');
            $error = $e;
          }
        }
      }
    }

    return $this->render($this->getTemplateManager()->getNewsletter('summary'), array(
                'newsletter' => $newsletter,
                'subscriber' => $this->getSubscriberManager()->getFirstSubscriber($newsletter->getId()),
                'mandantHash' => $this->getMandant()->getHash(),
                'testmailform' => $testmailform->createView(),
                'wizard' => $this->getWizardActionAnnotationHandler(),
                'error' => $error,
            ));
  }

  /**
   * @param NewsletterSubscriber $job
   */
  protected function send($job) {
    $this->get('ibrows_newsletter.mailer')->send($job);
  }

  public function summaryValidation(WizardActionHandler $handler) {
    $newsletter = $this->getNewsletter();
    $sendSettings = $this->getMandant()->getSendSettings();

    if (is_null($newsletter) || is_null($sendSettings)) {
      return $this->redirect($handler->getStepUrl($handler->getLastValidAnnotation()));
    }

    if (is_null($this->getSubscriberManager()->getFirstSubscriber($newsletter->getId()))) {
      return $this->redirect($this->generateUrl('ibrows_newsletter_subscriber'));
    }
  }

  /**
   * @Route("/generate/mailjobs", name="ibrows_newsletter_generate_mail_jobs")
   */
  public function generateMailJobsAction() {
    $newsletter = $this->getNewsletter();
    
    /**************************************************/
    // TO DO BULK UPDATE STATUS TO IMPROVE PERFORMANCE
    // TEMPORAL PATCH ONLY FOR MYSQL

    $this->getNewsletterSubscriberManager()->bulkUpdate($newsletter);
    /**************************************************/

    return $this->redirect($this->generateUrl('ibrows_newsletter_statistic_show', array('newsletterId' => $newsletter->getId())));
  }

  /**
   * @Route("/send", name="ibrows_newsletter_send")
   */
  public function sendAction() {
    $newsletter = $this->getNewsletter();
    if (is_null($newsletter)) {
      return $this->redirect($this->generateUrl('ibrows_newsletter_index', array(), true));
    }

    return $this->render($this->getTemplateManager()->getNewsletter('send'), array(
                'newsletter' => $newsletter
            ));
  }

  /**
   * @param Collection $blocks
   * @param array $blockParameters
   */
  protected function updateBlocksRecursive(Collection $blocks, array $blockParameters) {
    foreach ($blocks as $block) {
      $parameters = isset($blockParameters[$block->getId()]) ?
              $blockParameters[$block->getId()] : null;

      $provider = $this->getBlockProviderManager()->get($block->getProviderName());
      $provider->updateBlock($block, $parameters);

      $this->updateBlocksRecursive($block->getBlocks(), $blockParameters);
    }
  }

  /**
   * @param NewsletterInterface $newsletter
   * @param NewsletterInterface $cloneNewsletter
   */
  protected function cloneNewsletterBlocks(NewsletterInterface $newsletter, NewsletterInterface $cloneNewsletter) {
    foreach ($cloneNewsletter->getBlocks() as $parentBlock) {
      $cloneParentBlock = clone $parentBlock;
      $cloneParentBlock->setBlocks(array());
      $newsletter->addBlock($cloneParentBlock);

      $provider = $this->getBlockProviderManager()->get($cloneParentBlock->getProviderName());
      $provider->updateClonedBlock($cloneParentBlock);

      $this->loopCloneNewsletterBlocks($parentBlock, $cloneParentBlock);
    }
  }

  /**
   * @param BlockInterface $parentBlock
   * @param BlockInterface $cloneParentBlock
   */
  protected function loopCloneNewsletterBlocks(BlockInterface $parentBlock, BlockInterface $cloneParentBlock) {
    foreach ($parentBlock->getBlocks() as $childBlock) {
      $cloneChildBlock = clone $childBlock;
      $cloneChildBlock->setBlocks(array());
      $cloneParentBlock->addBlock($cloneChildBlock);

      $provider = $this->getBlockProviderManager()->get($cloneChildBlock->getProviderName());
      $provider->updateClonedBlock($cloneChildBlock);

      if ($childBlock->isCompound()) {
        $this->loopCloneNewsletterBlocks($childBlock, $cloneChildBlock);
      }
    }
  }

}
