<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;

class QueryManager
{
    public function buildQuery($repository) {
        $query = $repository->createQueryBuilder('p');
        return $query;
    }

    public function paginatedResults($query, $page = 1, $limit = 20, $ordering = 'id', $direction = 'DESC') {
        $firstResult = $page*$limit-$limit;

        $query->addOrderBy('p.'.$ordering, $direction);
        $query->setFirstResult( $firstResult );
        $query->setMaxResults( $limit );

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $results = $query->getQuery()->getResult();
        $total_results = count($paginator);
        $total_pages = (int)ceil($total_results/$limit);

        $next_page = ($page < $total_pages)? $page + 1 : 0;
        $previous_page = ($page > 1)? $page - 1 : 0;

        // create the paginated object
        $paginatedResult = new \stdClass();
        $paginatedResult->results = $results;

        $paginatedResult->pagination = new \stdClass();
        $paginatedResult->pagination->current_page =$page;
        $paginatedResult->pagination->total_pages =$total_pages;
        $paginatedResult->pagination->total_results =$total_results;
        $paginatedResult->pagination->next_page =$next_page;
        $paginatedResult->pagination->previous_page =$previous_page;

        return $paginatedResult;
    }

    public function findAll($page = 1, $limit = 20, $ordering = 'id', $direction = 'ASC') {
         $query = $this->buildQuery();
         return $this->paginatedResults($query, $page, $limit, $ordering, $direction);
    }

    public function findItem($query) {
        $query->setMaxResults(1);
        $results = $query->getQuery()->getResult();
        if(count($results)) {
            return $results[0];
        }
        return null;
    }
}
