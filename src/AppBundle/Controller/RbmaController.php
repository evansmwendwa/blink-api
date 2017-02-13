<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity\Video;

class RbmaController extends Controller
{
    /**
     * @Route("/rbma", name="rbma_episodes")
     */
    public function listAction(Request $request)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $em = $this->getDoctrine()->getManager();
        $episodes = $em->getRepository('AppBundle:Rbma')->findBy(
            array('published' => '1'),
            array('id' => 'DESC')
        );

        foreach($episodes as $episode) {
            $episode->setImageName($url.$episode->getImageName());
        }

        return $this->get('app.serializer')->JsonResponse($episodes);
    }

    /**
     * @Route("/rbma/{id}", name="rbma_episode")
     */
    public function viewAction(Request $request, $id)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $em = $this->getDoctrine()->getManager();
        $episode = $em->getRepository('AppBundle:Rbma')->findOneBy(
            array('published' => '1', 'id' => $id)
        );

        if (!$episode) {
            throw $this->createNotFoundException(
                'No episode found for id '.$id
            );
        }

        $episode->setImageName($url.$episode->getImageName());

        return $this->get('app.serializer')->JsonResponse($episode);
    }

}
