<?php

namespace Ibrows\Bundle\NewsletterBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;

class SubscriberType extends AbstractType {

  protected $managerName;
  protected $subscriberClass;

  public function __construct($managerName, $subscriberClass) {
    $this->managerName = $managerName;
    $this->subscriberClass = $subscriberClass;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
            ->add('subscribers', 'entity', array(
                'em' => $this->managerName,
                'class' => $this->subscriberClass,
                'multiple' => true,
                'expanded' => false,
            ))
    ;
  }

  /**
   * @return string
   */
  public function getName() {
    return 'ibrows_newsletterbundle_subscriber';
  }

}
