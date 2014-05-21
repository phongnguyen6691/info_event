<?php

namespace CMS\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $em    = $this->getDoctrine()->getManager();
        $query = $em->getRepository('CMSAdminBundle:Article')->findNewestSql();

        //Pager
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            5/*limit per page*/
        );

        $specialGroup = $em->getRepository('CMSAdminBundle:GroupArticle')->findSpecialSql();

        // parameters to template
        return array('pagination' => $pagination, 'specialGroup' => $specialGroup->getResult());
    }

    /**
     * @Route("/{slug}")
     */
    public function routAction($slug)
    {
        if($slug == 'admin') return $this->redirect($this->generateUrl('article'));

        $em    = $this->getDoctrine()->getManager();

        /*--- setup group page ---*/
        $group = $em->getRepository('CMSAdminBundle:GroupArticle')->findOneBy(
            array('url' => $slug, 'isActive' => 1)
        );
        if(count($group)){
            $articles = $em->getRepository('CMSAdminBundle:Article')->findByGroupSql(
                $group->getId()
            );
            return $this->render('CMSMainBundle:Default:group.html.twig',
                array('articles' => $articles->getResult(), 'groupName' => $group->getName())
            );
        }
        /*--- End setup group page ---*/


        /*---- setup article page ---*/
        $article = $em->getRepository('CMSAdminBundle:Article')->findOneBy(
            array('url' => $slug, 'isActive' => 1)
        );
        if(count($article)){
            $related = array();
            $idGroup = $article->getGroupArticle()->getId();

            if($idGroup)
                $related = $em->getRepository('CMSAdminBundle:Article')->findRelatedSql($idGroup, $article->getId());

            $specials = $article->getSpecialGroupArticle();
            $listSpecial = array();
            if(count($specials)){
                foreach($specials as $special){
                    $listSpecial[] = $special->getId();
                }
            }

            $article->setViews($article->getViews()+1);
            $em->flush();
            return $this->render('CMSMainBundle:Default:article.html.twig',
                array('article' => $article, 'listSpecial' => $listSpecial, 'related' => $related->getResult())
            );
        }
        /*---- End setup article page ---*/
    }

    /**
     * @Route("/menu")
     * @Template()
     */
    public function menuAction($position)
    {
        $menus = $this->getDoctrine()
            ->getRepository('CMSAdminBundle:GroupArticle')
            ->findAll();

        return array('menus' => $menus, 'position' => $position);
    }

    /**
     * @Route("/right")
     * @Template()
     */
    public function rightSlideBarAction($cpc, $listSpecial)
    {
        $specials = $this->getDoctrine()
            ->getRepository('CMSAdminBundle:SpecialGroupArticle')
            ->findAllSortByPositionSql();

        $viewBest = $this->getDoctrine()
            ->getRepository('CMSAdminBundle:Article')
            ->findViewBestSql();

        return array(
                'listSpecial' => $listSpecial ? $listSpecial : array(),
                'cpc' => isset($cpc) && $cpc ? $cpc : false,
                'specials' => $specials->getResult(),
                'viewBests' => $viewBest->getResult()
        );
    }
}
