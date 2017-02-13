<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity\Video;

class VideoController extends Controller
{
    /**
     * @Route("/videos", name="videos")
     */
    public function listAction(Request $request)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $em = $this->getDoctrine()->getManager();
        $videos = $em->getRepository('AppBundle:Video')->findBy(
            array('published' => '1'),
            array('releaseYear' => 'DESC')
        );

        foreach($videos as $video) {
            $video->setImageName($url.$video->getImageName());
        }

        return $this->get('app.serializer')->JsonResponse($videos);
    }

    /**
     * @Route("/videos/{id}", name="video")
     */
    public function viewAction(Request $request, $id)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $em = $this->getDoctrine()->getManager();
        $video = $em->getRepository('AppBundle:Video')->findOneBy(
            array('published' => '1', 'id' => $id)
        );

        if (!$video) {
            throw $this->createNotFoundException(
                'No Video found for id '.$id
            );
        }

        $video->setImageName($url.$video->getImageName());

        $related = $this->getRelatedVideos($request, $video->getId());

        return $this->get('app.serializer')->JsonResponse([
            'data' => $video,
            'related' => $related
        ]);
    }

    public function getRelatedVideos(Request $request, $id, $limit = 2) {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $em = $this->getDoctrine()->getManager();
        $videos = $em->getRepository('AppBundle:Video')->findBy(
            array('published' => '1')
        );

        $related = [];
        $index = 0;
        shuffle($videos);

        foreach($videos as $video) {
            if($index >= $limit) {
                break;
            }

            if($video->getId() !== $id) {
                $index++;
                $video->setImageName($url.$video->getImageName());
                $related[] = $video;
            }
        }

        return $related;
    }
}
