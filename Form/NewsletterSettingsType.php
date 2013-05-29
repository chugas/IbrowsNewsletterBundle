<?php

namespace Ibrows\Bundle\NewsletterBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;

class NewsletterSettingsType extends AbstractType {

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options) {
    $builder
      ->add('starttime', 'datetime');
  }

  /**
   * @return string
   */
  public function getName() {
    return 'ibrows_newsletterbundle_newsletter_settings';
  }

}
