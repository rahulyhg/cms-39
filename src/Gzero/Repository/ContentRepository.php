<?php namespace Gzero\Repository;

use Gzero\EloquentTree\Model\Tree;
use Gzero\Entity\ContentTranslation;
use Gzero\Entity\ContentType;
use Gzero\Entity\Content as C;
use Gzero\Entity\Route;
use Gzero\Entity\RouteTranslation;
use Gzero\Entity\User;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class ContentRepository
 *
 * @package    Gzero\Repository
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2014, Adrian Skierniewski
 */
class ContentRepository extends BaseRepository {

    /**
     * @var C
     */
    protected $model;

    /**
     * The events dispatcher
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * Content repository constructor
     *
     * @param C          $content Content model
     * @param Dispatcher $events  Events dispatcher
     */
    public function __construct(C $content, Dispatcher $events)
    {
        $this->model  = $content;
        $this->events = $events;
    }

    /**
     * Get content type by id.
     *
     * @param int $name Content id
     *
     * @return ContentType
     */
    public function getTypeByName($name)
    {
        return $this->newORMQuery()->getRelation('type')->getQuery()->find($name);
    }

    /**
     * Get content translation by id.
     *
     * @param int $id Content Translation id
     *
     * @return ContentTranslation
     */
    public function getTranslationById($id)
    {
        return $this->newORMQuery()->getRelation('translations')->getQuery()->find($id);
    }

    /**
     * Get content entity by url address
     *
     * @param string $url      Url address
     * @param string $langCode Lang code
     *
     * @return C
     */
    public function getByUrl($url, $langCode)
    {
        return $this->newORMQuery()
            ->join(
                'Routes',
                function ($join) {
                    $join->on('Contents.id', '=', 'Routes.routableId')
                        ->where('Routes.RoutableType', '=', 'Gzero\Entity\Content');
                }
            )
            ->join(
                'RouteTranslations',
                function ($join) use ($langCode) {
                    $join->on('Routes.id', '=', 'RouteTranslations.routeId')
                        ->where('RouteTranslations.langCode', '=', $langCode);
                }
            )->where('RouteTranslations.url', '=', $url)
            ->first(['Contents.*']);
    }

    /**
     * Get all children nodes to specific node
     *
     * @param C        $content  Tree node
     * @param array    $criteria Filter criteria
     * @param array    $orderBy  Array of columns
     * @param int|null $page     Page number
     * @param int|null $pageSize Limit results
     *
     * @throws RepositoryException
     * @return Collection
     */
    public function getTranslations(C $content, array $criteria, array $orderBy = [], $page = 1, $pageSize = self::ITEMS_PER_PAGE)
    {
        $query = $content->translations(false);
        $this->handleFilterCriteria($this->getTranslationsTableName(), $criteria, $query);
        $count = clone $query;
        $this->handleOrderBy(
            $this->getTranslationsTableName(),
            $orderBy,
            $query,
            function ($query) { // default order by
                $query->orderBy('ContentTranslations.isActive', 'DESC');
            }
        );
        $results = $query
            ->offset($pageSize * ($page - 1))
            ->limit($pageSize)
            ->get(['ContentTranslations.*']);
        \Paginator::setCurrentPage($page); // We need to set current page because laravel use input to set this property
        return \Paginator::make($results->all(), $count->select('ContentTranslations.id')->count(), $pageSize);
    }

    /*
    |--------------------------------------------------------------------------
    | START TreeRepository
    |--------------------------------------------------------------------------
    */

    ///**
    // * Get all ancestors nodes to specific node
    // *
    // * @param TreeNode $node    Node to find ancestors
    // * @param int      $hydrate Doctrine2 hydrate mode. Default - Query::HYDRATE_ARRAY
    // *
    // * @return mixed
    // */
    //public function getAncestors(TreeNode $node, $hydrate = Query::HYDRATE_ARRAY)
    //{
    //    // root does not have ancestors
    //    if ($node->getPath() != '/') {
    //        $ancestorsIds = $node->getAncestorsIds();
    //        $qb           = $this->newQB()
    //            ->select('n')
    //            ->from($this->getClassName(), 'n')
    //            ->where('n.id IN(:ids)')
    //            ->setParameter('ids', $ancestorsIds)
    //            ->orderBy('n.level');
    //
    //        $nodes = $qb->getQuery()->getResult($hydrate);
    //        return $nodes;
    //    }
    //    return [];
    //}

    ///**
    // * Get all descendants nodes to specific node
    // *
    // * @param TreeNode $node    Node to find descendants
    // * @param bool     $tree    If you want get in tree structure instead of list
    // * @param int      $hydrate Doctrine2 hydrate mode. Default - Query::HYDRATE_ARRAY
    // *
    // * @return mixed
    // */
    //public function getDescendants(TreeNode $node, $tree = false, $hydrate = Query::HYDRATE_ARRAY)
    //{
    //    $qb = $this->newQB()
    //        ->from($this->getClassName(), 'n')
    //        ->where('n.path LIKE :path')
    //        ->setParameter('path', $node->getChildrenPath() . '%')
    //        ->orderBy('n.level');
    //    if ($tree) {
    //        $qb->select('n', 'c')
    //            ->leftJoin(
    //                'n.children',
    //                'c',
    //                'WITH',
    //                'c.level > :nodeLevel'
    //            )
    //            ->setParameter('nodeLevel', $node->getLevel());
    //    } else {
    //        $qb->select('n');
    //    }
    //    $nodes = $qb->getQuery()->getResult($hydrate);
    //    if ($tree) {
    //        return array_filter(
    //            $nodes,
    //            function ($item) use ($node) { // We return children's array because we don't have one root
    //                // @TODO Ugly HAX ['level']
    //                $level = (is_array($item)) ? @$item['level'] : $item->getLevel();
    //                return ($level == $node->getLevel() + 1);
    //            }
    //        );
    //    } else {
    //        return $nodes;
    //    }
    //}

    ///**
    // *
    // *
    // * @param TreeNode $node     Node to find children
    // * @param array    $criteria Array of conditions
    // * @param array    $orderBy  Array of columns
    // * @param int|null $limit    Limit results
    // * @param int|null $offset   Start from
    // *
    // * @return mixed
    // * @SuppressWarnings("unused") We'll refactor whole repository
    // */
    //public function getChildren(TreeNode $node, array $criteria = [], array $orderBy = [], $limit = null, $offset = null)
    //{
    //    $qb = $this->newQB()
    //        ->select('c,t,a')
    //        ->from($this->getClassName(), 'c')
    //        ->leftJoin('c.translations', 't', 'WITH', 't.isActive = 1')
    //        ->leftJoin('c.author', 'a')
    //        ->where('c.path = :path')
    //        ->setParameter('path', $node->getChildrenPath())
    //        ->setFirstResult($offset)
    //        ->setMaxResults($limit);
    //
    //    foreach ($orderBy as $sort => $order) {
    //        $qb->orderBy($sort, $order);
    //    }
    //    return $qb->getQuery()->getArrayResult();
    //}

    /**
     * Get all descendants nodes to specific node
     *
     * @param Tree  $node     Tree node
     * @param array $criteria Filter criteria
     * @param array $orderBy  Array of columns
     * @param bool  $tree     If you want get in tree structure instead of list
     *
     * @throws RepositoryException
     * @return Collection
     */
    public function getDescendants(Tree $node, array $criteria, array $orderBy = [], $tree = false)
    {
        $query = $node->findDescendants();
        $this->handleTranslationsJoin($criteria, $orderBy, $query);
        $this->handleFilterCriteria($this->getTableName(), $criteria, $query);
        $this->handleOrderBy(
            $this->getTableName(),
            $orderBy,
            $query,
            $this->contentDefaultOrderBy()
        );
        $results = $query->get(['Contents.*']);
        $this->listEagerLoad($results);
        if ($tree) {
            return $node->buildTree($results);
        }
        return $results;
    }

    /**
     * Get all children nodes to specific node
     *
     * @param Tree     $node     Tree node
     * @param array    $criteria Filter criteria
     * @param array    $orderBy  Array of columns
     * @param int|null $page     Page number
     * @param int|null $pageSize Limit results
     *
     * @throws RepositoryException
     * @return Collection
     */
    public function getChildren(Tree $node, array $criteria, array $orderBy = [], $page = 1, $pageSize = self::ITEMS_PER_PAGE)
    {
        $query = $node->children();
        $this->handleTranslationsJoin($criteria, $orderBy, $query);
        $this->handleFilterCriteria($this->getTableName(), $criteria, $query);
        $this->handleAuthorJoin($query);
        $count = clone $query;
        $this->handleOrderBy(
            $this->getTableName(),
            $orderBy,
            $query,
            $this->contentDefaultOrderBy()
        );
        $results = $query
            ->offset($pageSize * ($page - 1))
            ->limit($pageSize)
            ->get(['Contents.*']);
        $this->listEagerLoad($results);
        \Paginator::setCurrentPage($page); // We need to set current page because laravel use input to set this property
        return \Paginator::make($results->all(), $count->select('Contents.id')->count(), $pageSize);
    }

    ///**
    // * Get all siblings nodes to specific node
    // *
    // * @param TreeNode $node     Node to find siblings
    // * @param array    $criteria Array of conditions
    // * @param array    $orderBy  Array of columns
    // * @param int|null $limit    Limit results
    // * @param int|null $offset   Start from
    // *
    // * @return mixed
    // */
    //public function getSiblings(TreeNode $node, array $criteria = [], array $orderBy = null, $limit = null, $offset = null)
    //{
    //    $siblings = parent::findBy(array_merge($criteria, ['path' => $node->getPath()]), $orderBy, $limit, $offset);
    //    // we skip $node
    //    return array_filter(
    //        $siblings,
    //        function ($var) use ($node) {
    //            return $var->getId() != $node->getId();
    //        }
    //    );
    //}

    /**
     * Get all contents with specific criteria
     *
     * @param array    $criteria Filter criteria
     * @param array    $orderBy  Array of columns
     * @param int|null $page     Page number
     * @param int|null $pageSize Limit results
     *
     * @throws RepositoryException
     * @return Collection
     */
    public function getContents(array $criteria, array $orderBy = [], $page = 1, $pageSize = self::ITEMS_PER_PAGE)
    {
        $query = $this->newORMQuery();
        $this->handleTranslationsJoin($criteria, $orderBy, $query);
        $this->handleFilterCriteria($this->getTableName(), $criteria, $query);
        $this->handleAuthorJoin($query);
        $count = clone $query;
        $this->handleOrderBy(
            $this->getTableName(),
            $orderBy,
            $query,
            $this->contentDefaultOrderBy()
        );
        $results = $query
            ->offset($pageSize * ($page - 1))
            ->limit($pageSize)
            ->get(['Contents.*']);
        $this->listEagerLoad($results);
        \Paginator::setCurrentPage($page); // We need to set current page because laravel use input to set this property
        return \Paginator::make($results->all(), $count->select('Contents.id')->count(), $pageSize);
    }

    /**
     * Get translation of specified content by id.
     *
     * @param C   $content Content entity
     * @param int $id      Content Translation id
     *
     * @return ContentTranslation
     */
    public function getContentTranslationById(C $content, $id)
    {
        return $content->translations(false)->where('id', '=', $id)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | END TreeRepository
    |--------------------------------------------------------------------------
    */

    /**
     * Create specific content entity
     *
     * @param array     $data   Content entity to persist
     * @param User|null $author Author entity
     *
     * @return C
     */
    public function create(Array $data, User $author = null)
    {
        $content = $this->newQuery()->transaction(
            function () use ($data, $author) {
                $url          = '';
                $translations = array_get($data, 'translations'); // Nested relation fields
                // Content
                $content = new C();
                $content->fill($data);
                $content->author()->associate($author);
                if (!empty($data['parentId'])) {
                    $parent = $this->getById($data['parentId']);
                    if (!empty($parent)) {
                        $content->setChildOf($parent);
                        $url = $parent->getUrl($translations['langCode']) . '/';
                    } else {
                        throw new RepositoryException('Parent node id: ' . $data['parentId'] . ' doesn\'t exist');
                    }
                } else {
                    $content->setAsRoot();
                }
                // Route
                $route = new Route(['isActive' => 1]);
                $content->route()->save($route);
                // Route translations
                $routeTranslation           = new RouteTranslation();
                $routeTranslation->langCode = $translations['langCode'];
                /** @TODO Check for duplicated url */
                $routeTranslation->url      = $url . Str::slug($translations['title']);
                $routeTranslation->isActive = 1;
                // Content translations
                $translation = new ContentTranslation();
                $translation->fill($translations);
                $translation->isActive = 1; // Because this is first translation
                $content->translations()->save($translation);
                $route->translations()->save($routeTranslation);
                return $content;
            }
        );
        $this->events->fire('content.created', [$content]);
        return $content;
    }

    /**
     * Create translation for specified content entity
     *
     * @param C     $content Content entity
     * @param array $data    new data to save
     *
     * @return ContentTranslation
     */
    public function createTranslation(C $content, Array $data)
    {
        $translation = $this->newQuery()->transaction(
            function () use ($content, $data) {
                // Set all translation of this content as inactive
                $this->disableActiveTranslations($content->id, $data['langCode']);
                $translation = new ContentTranslation();
                $translation->fill($data);
                $translation->isActive = 1; // Because only recent translation is active
                $content->translations()->save($translation);
                return $translation;
            }
        );
        $this->events->fire('content.translation.created', [$content, $translation]);
        return $translation;
    }

    /**
     * Update specific content entity
     *
     * @param C         $content  Content entity
     * @param array     $data     new data to save
     * @param User|null $modifier User entity
     *
     * @return C
     * @SuppressWarnings("unused")
     */
    public function update(C $content, Array $data, User $modifier = null)
    {
        $content = $this->newQuery()->transaction(
            function () use ($content, $data, $modifier) {
                $content->fill($data);
                if (!empty($data['parentId'])) {
                    $parent = $this->getById($data['parentId']);
                    if (!empty($parent)) {
                        $content->setChildOf($parent);
                    } else {
                        $content->setAsRoot();
                    }
                } else {
                    $content->save();
                }
                return $content;
            }
        );
        $this->events->fire('content.updated', [$content]);
        return $content;
    }

    /**
     * Delete specific content entity
     *
     * @param C $content Content entity to delete
     *
     * @return boolean
     */
    public function delete(C $content)
    {
        return $content->delete();
    }

    /**
     * Default orderBy for content query
     *
     * @return callable
     */
    private function contentDefaultOrderBy()
    {
        return function ($query) {
            $query->orderBy('Contents.weight', 'ASC');
            $query->orderBy('Contents.createdAt', 'DESC');
        };
    }

    /**
     * Handle joining content translations table based on provided criteria
     *
     * @param array $criteria Array with filter criteria
     * @param array $orderBy  Array with orderBy
     * @param mixed $query    Eloquent query object
     *
     * @throws RepositoryException
     * @return array
     */
    private function handleTranslationsJoin(array &$criteria, array $orderBy, $query)
    {
        if (!empty($criteria['lang'])) {
            $query->leftJoin(
                'ContentTranslations',
                function ($join) use ($criteria) {
                    $join->on('Contents.id', '=', 'ContentTranslations.contentId')
                        ->where('ContentTranslations.langCode', '=', $criteria['lang']['value']);
                }
            );
            unset($criteria['lang']);
        } else {
            if (!empty($orderBy)) {
                throw new RepositoryException('Repository Validation Error: \'lang\' criteria is required', 500);
            }
        }

    }

    /**
     * Handle joining users table
     *
     * @param mixed $query Eloquent query object
     *
     * @return array
     */
    private function handleAuthorJoin($query)
    {
        $query->leftJoin(
            'Users',
            function ($join) {
                $join->on('Contents.authorId', '=', 'Users.id');
            }
        );
    }

    /**
     * Eager load relations for eloquent collection
     *
     * @param Collection $results Eloquent collection
     *
     * @return void
     */
    private function listEagerLoad($results)
    {
        $results->load('route.translations', 'translations');
    }
}
