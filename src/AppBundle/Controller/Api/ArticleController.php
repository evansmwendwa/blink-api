<?php

namespace AppBundle\Controller\Api;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\QueryManager;

class ArticleController extends Controller
{
    /**
     * @Route("/articles/{page}", name="articles")
     */
    public function listAction(Request $request, $page = 1)
    {
        $vich_uploader = $this->get('vich_uploader.custom_directory_namer');
        $imager = $this->get('Imager');
        $baseUrl = $vich_uploader->getUploadsUrl();

        $repository = $this->getDoctrine()->getRepository('AppBundle:Article');

        $qm = new QueryManager();

        $query = $qm->buildQuery($repository);
        $query->andWhere('p.published = :published');
        $query->setParameter('published', true);

        // filter for publishedAt date
        $query->andWhere('p.publishedAt <= :nowDate');
        $query->setParameter('nowDate', new \DateTimeImmutable());

        $query->addOrderBy('p.publishedAt', 'DESC');

        $data = $qm->paginatedResults($query, $page);

        foreach($data->results as $article) {
            $imagePath = $vich_uploader->getUploadPath().'/'.$article->getImagePath();
            $image = $imager->filter($vich_uploader->getUploadDir().'/'.$article->getImagePath(),'news_thumb');
            $article->setImagePath($image);
        }

        return $this->get('app.serializer')->JsonResponse($data);
    }

    /**
     * @Route("/article/{id}", name="article")
     */
    public function viewAction(Request $request, $id)
    {
        $baseUrl = $this->get('vich_uploader.custom_directory_namer')->getUploadsUrl();

        $repository = $this->getDoctrine()->getRepository('AppBundle:Article');

        $qm = new QueryManager();

        $query = $qm->buildQuery($repository);
        $query->andWhere('p.published = :published');
        $query->setParameter('published', true);

        $query->andWhere('p.id = :id');
        $query->setParameter('id', $id);

        // filter for publishedAt date
        $query->andWhere('p.publishedAt <= :nowdate');
        $query->setParameter('nowdate', new \DateTimeImmutable());

        $article = $qm->findItem($query);

        if (!$article) {
            throw $this->createNotFoundException(
                'No Article found for id '.$id
            );
        }

        $article->setImagePath($baseUrl.'/'.$article->getImagePath());

        // apply oEmbed filter;
        $oEmbed = $this->get('open_embed');
        $content = $oEmbed->parse($article->getContent());
        $article->setContent($content);

        return $this->get('app.serializer')->JsonResponse($article);
    }
}
