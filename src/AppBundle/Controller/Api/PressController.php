<?php

namespace AppBundle\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class PressController extends Controller
{
    /**
     * @Route("/press", name="press_links")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $links = $em->getRepository('AppBundle:Press')->findBy(
            ['published' => '1'],
            ['id' => 'DESC']
        );

        return $this->get('app.serializer')->JsonResponse($links);
    }
}
