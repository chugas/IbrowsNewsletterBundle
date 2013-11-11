<?php

namespace Ibrows\Bundle\NewsletterBundle\Block\Provider;

use Ibrows\Bundle\NewsletterBundle\Model\Block\BlockInterface;
use Doctrine\ORM\Query;

class NewsProvider extends AbstractProvider {

  protected $templating;
  
  protected $entity_manager;

  const PROVIDER_OPTION_IDS = 'ids';

  public function __construct($templating, $entity_manager) {
    $this->templating = $templating;
    $this->entity_manager = $entity_manager;
  }

  public function getBlockEditContent(BlockInterface $block) {
    return $this->templating->render('SuccessNewsletterBundle:News:input.html.twig', array('content' => $this->getBlockDisplayContent($block), 'id' => $block->getId()));
  }

  public function getBlockDisplayContent(BlockInterface $block) {
    return html_entity_decode($block->getContent());
  }

  public function updateBlock(BlockInterface $block, $update) {
    if(empty($update)) return;
    
    $ids = explode(',', $update);

    $query = $this->entity_manager->createQueryBuilder()
            ->select('n')
            ->from('PortalBundle:News', 'n')
            ->where('n.published = 1')
            ->andWhere('n.status = :status')
            ->andWhere('n.id IN (:ids)');

    $query->setParameter('ids', $ids);
    $query->setParameter('status', 'aceptada');

    $news = $query->getQuery()->execute(array());

    $count = count($news);
    $limit = ceil($count / 2);
    $column_one = array_slice($news, 0, $limit);
    $column_two = array_slice($news, $limit);

    $block->setProviderOption(self::PROVIDER_OPTION_IDS, $ids);
    $template = $this->templating->render('SuccessNewsletterBundle:News:mail.html.twig', array('column_one' => $column_one, 'column_two' => $column_two));

    $block->setContent($template);
  }
  
  protected function getIds(BlockInterface $block) {
    return $block->getProviderOption(self::PROVIDER_OPTION_IDS);
  }

  protected function setIds(BlockInterface $block, $ids) {
    return $block->setProviderOption(self::PROVIDER_OPTION_IDS, $ids);
  }

}