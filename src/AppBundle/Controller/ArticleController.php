<?php

namespace AppBundle\Controller;

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
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $repository = $this->getDoctrine()->getRepository('AppBundle:Article');

        $qm = new QueryManager();

        $query = $qm->buildQuery($repository);
        $query->andWhere('p.published = :published');
        $query->setParameter('published', true);

        // filter for publishedAt date
        $query->andWhere('p.publishedAt >= :publishDate');
        $query->setParameter('publishDate', new \DateTimeImmutable());

        $query->addOrderBy('p.publishedAt', 'DESC');

        $data = $qm->paginatedResults($query, $page);

        foreach($data->results as $article) {
            $article->setImageName($url.$article->getImageName());
        }

        //// NOTE JMS Serializer does not serialize stdClass.
        //// Thats why we are casting pagination to array below.
        //// future update should be done to fix QueryManager to
        //// stop usage of stdClass
        return $this->get('app.serializer')->JsonResponse([
            'results' => $data->results,
            'pagination' => (array)$data->pagination
        ]);
    }

    /**
     * @Route("/article/{id}", name="article")
     */
    public function viewAction(Request $request, $id)
    {
        $url = $baseurl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath();
        $url .='/images/products/';

        $repository = $this->getDoctrine()->getRepository('AppBundle:Article');

        $qm = new QueryManager();

        $query = $qm->buildQuery($repository);
        $query->andWhere('p.published = :published');
        $query->setParameter('published', true);

        $query->andWhere('p.id = :id');
        $query->setParameter('id', $id);

        // filter for publishedAt date
        $query->andWhere('p.publishedAt >= :publishDate');
        $query->setParameter('publishDate', new \DateTimeImmutable());

        $article = $qm->findItem($query);

        $article->setImageName($url.$article->getImageName());

        //// NOTE JMS Serializer does not serialize stdClass.
        //// Thats why we are casting pagination to array below.
        //// future update should be done to fix QueryManager to
        //// stop usage of stdClass
        return $this->get('app.serializer')->JsonResponse($article);
    }
}
