<?php
/**
 * Created by PhpStorm.
 * User: cgcomputadoras
 * Date: 7/6/2017
 * Time: 2:03 PM
 */

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class MaterialesController extends Controller
{
    //muestra la lista de materiales
    /**
     * @Route("/materiales", name="show_materiales")
     */
    public function showUsersAction(){

        $em=$this->getDoctrine()->getManager();
        $materiales=$em->getRepository('AppBundle:Materiales')->findAll();

        if (!$materiales) {
            throw $this->createNotFoundException('Materiales no encontrados');
        }


        return $this->render('default/showmateriales.html.twig',array(
            'materiales'=>$materiales,

        ));

    }


}