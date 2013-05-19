<?php

namespace Ibrows\Bundle\NewsletterBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class TestMailType extends AbstractType {

  protected $defaultMail;

  public function __construct($defaultMail = null) {
    $this->defaultMail = $defaultMail;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
            ->add('email', 'email', array('data' => $this->defaultMail))
    ;
  }

  /**
   * @return string
   */
  public function getName() {
    return 'ibrows_newsletterbundle_testmail';
  }

}
