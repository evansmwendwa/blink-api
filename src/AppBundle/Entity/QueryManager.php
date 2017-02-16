<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;

class Pagination
{
    public $current_page;
    public $total_pages;
    public $total_results;
    public $next_page;
    public $previous_page;

    public function __construct(
        $current_page,
        $total_pages,
        $total_results,
        $next_page,
        $previous_page
    ) {
        $this->current_page = $current_page;
        $this->total_pages = $total_pages;
        $this->total_results = $total_results;
        $this->next_page = $next_page;
        $this->previous_page = $previous_page;
    }
}

class PaginatedResult
{
    public $results;
    public $pagination;

    public function __construct($results, $pagination) {
        $this->results = $results;
        $this->pagination = $pagination;
    }
}

class QueryManager
{
    public function buildQuery($repository) {
        $query = $repository->createQueryBuilder('p');
        return $query;
    }

    public function paginatedResults(
        $query, $page = 1, $limit = 15,
        $ordering = 'id', $direction = 'DESC'
    ) {
        $page = (int)$page;
        $limit = (int)$limit;

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
        $pagination = new Pagination(
            $page, $total_pages, $total_results,
            $next_page, $previous_page
        );

        $paginatedResult = new PaginatedResult($results, $pagination);

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
