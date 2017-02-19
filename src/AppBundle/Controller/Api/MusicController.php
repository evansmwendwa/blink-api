<?php

namespace AppBundle\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use GuzzleHttp;

class MusicController extends Controller
{
    /**
     * @Route("/music/tracks", name="music_tracks")
     */
    public function tracksAction(Request $request)
    {
        $path = 'https://itunes.apple.com/lookup?id=336050465&entity=song';

        $client = new GuzzleHttp\Client();

        $body = $client->get($path)->getBody();

        $result = json_decode($body,1);

        return $this->get('app.serializer')->JsonResponse($result);

    }

    /**
     * @Route("/music/albums", name="music_albums")
     */
    public function albumsAction(Request $request)
    {
        $path = 'https://itunes.apple.com/lookup?id=336050465&entity=album';

        $client = new GuzzleHttp\Client();

        $body = $client->get($path)->getBody();

        $result = json_decode($body,1);

        return $this->get('app.serializer')->JsonResponse($result);

    }

    /**
     * @Route("/music/album/{id}", name="music_album")
     */
    public function albumAction(Request $request, $id)
    {
        $path = 'https://itunes.apple.com/lookup?id='.$id.'&entity=song';

        $client = new GuzzleHttp\Client();

        $body = $client->get($path)->getBody();

        $result = json_decode($body,1);

        return $this->get('app.serializer')->JsonResponse($result);

    }
}
