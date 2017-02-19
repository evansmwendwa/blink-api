<?php

namespace AppBundle\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;

class GalleryController extends Controller
{
    /**
     * @Route("/gallery", name="gallery")
     */
    public function indexAction(Request $request)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/uploads/gallery/';

        $dir = realpath(__DIR__ .'/../../../../web/uploads/gallery');

        $finder = new Finder();
        $finder->files()->in($dir);

        $files = [];

        foreach ($finder as $file) {
            $files[] = [
                'url' => $url.$file->getRelativePathname(),
                'filename' => $file->getRelativePathname()
            ];
        }

        shuffle($files);

        return $this->get('app.serializer')->JsonResponse($files);

    }
}
