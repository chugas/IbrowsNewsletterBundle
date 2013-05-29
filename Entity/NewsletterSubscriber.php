<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ibrows\Bundle\NewsletterBundle\Entity;

use Ibrows\Bundle\NewsletterBundle\Model\Newsletter\NewsletterSubscriber as AbstractNewsletterSubscriber;
use Doctrine\ORM\Mapping as ORM;

class NewsletterSubscriber extends AbstractNewsletterSubscriber { 

  /**
   * @ORM\Column(type="text", nullable=true)
   */
  protected $error;

  /**
   * @ORM\Column(type="string")
   */
  protected $status;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  protected $completed; 

}