<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Album;
use App\Entity\Collection;
use App\Entity\Item;
use App\Entity\Tag;
use App\Entity\Wishlist;
use App\Enum\VisibilityEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Routing\RouterInterface;

class Autocompleter
{
    private ContextHandler $contextHandler;

    private EntityManagerInterface $em;

    private array $params;

    private RouterInterface $router;

    private FeatureChecker $featureChecker;

    public function __construct(ContextHandler $contextHandler, EntityManagerInterface $em, RouterInterface $router, FeatureChecker $featureChecker)
    {
        $this->contextHandler = $contextHandler;
        $this->em = $em;
        $this->router = $router;
        $this->featureChecker = $featureChecker;
    }

    public function findForAutocomplete(string $term): array
    {
        $this->params = [];
        $queryParts = [];
        $queryParts[] = $this->buildRequestForGivenTable($this->em->getClassMetadata(Collection::class)->getTableName(), 'title', $term, 'collection');
        $queryParts[] = $this->buildRequestForGivenTable($this->em->getClassMetadata(Item::class)->getTableName(), 'name', $term, 'item');
        if ($this->featureChecker->isFeatureEnabled('tags')) {
            $queryParts[] = $this->buildRequestForGivenTable($this->em->getClassMetadata(Tag::class)->getTableName(), 'label', $term, 'tag');
        }
        if ($this->featureChecker->isFeatureEnabled('tags')) {
            $queryParts[] = $this->buildRequestForGivenTable($this->em->getClassMetadata(Album::class)->getTableName(), 'title', $term, 'album');
        }
        if ($this->featureChecker->isFeatureEnabled('tags')) {
            $queryParts[] = $this->buildRequestForGivenTable($this->em->getClassMetadata(Wishlist::class)->getTableName(), 'name', $term, 'wishlist');
        }
        $sql = implode(' UNION ', $queryParts);

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('counter', 'counter');
        $counterSql = "SELECT COUNT(*) AS counter FROM ({$sql}) AS x";
        $query = $this->em->createNativeQuery($counterSql, $rsm);
        foreach ($this->params as $key => $value) {
            $query->setParameter($key + 1, $value);
        }
        $counter =  $query->getSingleScalarResult();

        //Get the 5 most relevant results
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('label', 'label');
        $rsm->addScalarResult('type', 'type');
        $sql .= "
            ORDER BY relevance DESC, seenCounter DESC, label ASC
            LIMIT 5
        ";
        $query = $this->em->createNativeQuery($sql, $rsm);
        foreach ($this->params as $key => $value) {
            $query->setParameter($key + 1, $value);
        }
        $results = $query->getResult();
        $results = array_map(function ($result) {
            $route = $this->router->generate("app_{$result['type']}_show", ['id' => $result['id']]);
            $result['url'] = $this->contextHandler->getRouteContext($route);
            unset($result['id']);

            return $result;
        }, $results);

        return [
            'results' => $results,
            'totalResultsCounter' => $counter
        ];
    }

    private function buildRequestForGivenTable(string $collectionTable, string $labelProperty, string $term, string $type) : string
    {
        $user = $this->contextHandler->getContextUser();
        $terms = explode(' ', $term);
        $term = implode('%', $terms);

        $sql = "
            SELECT id AS id, {$labelProperty} AS label, '{$type}' AS type, seen_counter AS seenCounter,
                (CASE 
                     WHEN LOWER({$labelProperty}) = LOWER(?) THEN 2 -- exact match
                     WHEN LOWER({$labelProperty}) LIKE LOWER(?) THEN 1 -- end with                     
                     ELSE 0
                END) AS relevance
            FROM {$collectionTable} 
            WHERE owner_id = ?
            AND LOWER({$labelProperty}) LIKE LOWER(?)
        ";

        $this->params[] = $term;
        $this->params[] = $term . '%';
        $this->params[] = $user->getId();
        $this->params[] = '%'. $term . '%';

        if ($this->em->getFilters()->isEnabled('visibility')) {
            $sql .= " AND visibility = ?";
            $this->params[] = VisibilityEnum::VISIBILITY_PUBLIC;
        };

        return $sql;
    }
}
