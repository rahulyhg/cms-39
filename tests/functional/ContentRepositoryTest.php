<?php namespace functional;

use Gzero\Entity\Content;
use Gzero\Entity\User;
use Gzero\Repository\ContentRepository;
use Illuminate\Events\Dispatcher;

require_once(__DIR__ . '/../stub/TestSeeder.php');
require_once(__DIR__ . '/../stub/TestTreeSeeder.php');

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class ContentRepositoryTest
 *
 * @package    functional
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2015, Adrian Skierniewski
 */
class ContentRepositoryTest extends \EloquentTestCase {

    /**
     * @var ContentRepository
     */
    protected $repository;

    public function setUp()
    {
        parent::setUp();
        $this->repository = new ContentRepository(new Content(), new Dispatcher());
        $this->app['artisan']->call('db:seed', ['--class' => 'TestSeeder']); // Relative to tests/app/
    }

    /**
     * @test
     */
    public function can_get_content_by_url()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getByUrl('example-title', 'en');
        $this->assertNotSame($content, $newContent);
        $this->assertEquals($content->id, $newContent->id);
    }

    /**
     * @test
     */
    public function can_create_content()
    {
        $author  = User::find(1);
        $content = $this->repository->create(
            [
                'type'             => 'content',
                'isOnHome'         => true,
                'isCommentAllowed' => true,
                'isPromoted'       => true,
                'isSticky'         => true,
                'isActive'         => true,
                'publishedAt'      => date('Y-m-d H:i:s'),
                'translations'     => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ],
            $author
        );

        $newContent       = $this->repository->getById($content->id);
        $newContentRoute  = $newContent->route->translations()->first();
        $newContentAuthor = $newContent->author;
        // Content
        $this->assertNotSame($content, $newContent);
        $this->assertEquals($content->id, $newContent->id);
        $this->assertEquals($content->type, $newContent->type);
        $this->assertEquals($content->isOnHome, $newContent->isOnHome);
        $this->assertEquals($content->isCommentAllowed, $newContent->isCommentAllowed);
        $this->assertEquals($content->isPromoted, $newContent->isPromoted);
        $this->assertEquals($content->isSticky, $newContent->isSticky);
        $this->assertEquals($content->isActive, $newContent->isActive);
        $this->assertEquals($content->publishedAt, $newContent->publishedAt);
        // Author
        $this->assertEquals($author->id, $newContent->authorId);
        $this->assertEquals($author->email, $newContentAuthor['email']);
        // Route
        $this->assertEquals('en', $newContentRoute['langCode']);
        $this->assertEquals('example-title', $newContentRoute['url']);
    }

    /**
     * @test
     */
    public function can_create_content_without_author()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->assertNotSame($content, $newContent);
        $this->assertNull($newContent->author);
    }

    /**
     * @test
     */
    public function can_create_and_get_content_translation()
    {
        $content          = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ]
        );
        $newContent       = $this->repository->getById($content->id);
        $translation      = $this->repository->createTranslation(
            $newContent,
            [
                'langCode' => 'en',
                'title'    => 'New example title',
                'body'     => 'New example body'
            ]
        );
        $firstTranslation = $this->repository->getContentTranslationById($newContent, 1);
        $newTranslation   = $this->repository->getContentTranslationById($newContent, 2);
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($translation, $firstTranslation);
        // Check if previous translation are inactive
        $this->assertFalse((bool) $firstTranslation->isActive);
        // Check if a new translation has been added
        $this->assertEquals('en', $newTranslation->langCode);
        $this->assertEquals('New example title', $newTranslation->title);
        $this->assertEquals('New example body', $newTranslation->body);
        $this->assertEquals($newContent->id, $newTranslation->contentId);
    }

    /**
     * @test
     */
    public function can_update_content()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'isOnHome'     => false,
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->repository->update(
            $newContent,
            [
                'isOnHome' => true,
            ]
        );
        $updatedContent = $this->repository->getById($newContent->id);
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($newContent, $updatedContent);
        $this->assertEquals(true, $updatedContent->isOnHome);
    }

    /**
     * @test
     */
    public function can_delete_content()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->assertNotSame($content, $newContent);
        $this->repository->delete($newContent);
        // content has been removed?
        $this->assertNull($this->repository->getById($newContent->id));
        // route has been removed?
        $this->assertNull($newContent->route()->first());
        // content translations has been removed?
        $this->assertNull($newContent->translations()->first());
    }

    /**
     * @test
     */
    public function can_delete_content_translation()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example title',
                    'body'     => 'Example body'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->assertNotSame($content, $newContent);
        $this->repository->deleteTranslation($newContent->translations()->first());
        // content translations has been removed?
        $this->assertNull($newContent->translations()->first());

    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_content_type()
    {
        $this->repository->create(
            [
                'type'         => 'fakeType',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example category title'
                ]
            ]
        );
    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_content_url()
    {
        $this->repository->getByUrl('example-title', 'en');
    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_content_translation()
    {
        $this->repository->create(['type' => 'category']);
    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_parent_route_translation()
    {
        $category    = $this->repository->create(
            [
                'type'         => 'category',
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example category title'
                ]
            ]
        );
        $newCategory = $this->repository->getById($category->id);
        $this->repository->create(
            [
                'type'         => 'content',
                'parentId'     => $newCategory->id,
                'translations' => [
                    'langCode' => 'pl',
                    'title'    => 'Example content title'
                ]
            ]
        );
    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_parent()
    {
        $this->repository->create(
            [
                'type'         => 'content',
                'parentId'     => 1,
                'translations' => [
                    'langCode' => 'pl',
                    'title'    => 'Example content title'
                ]
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | START Tree tests
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     */
    public function can_get_roots()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $roots = $this->repository->getRoots(
            [],
            [],
            null
        );
        foreach ($roots as $node) {
            $this->assertNull($node->parentId);
            $this->assertEquals(0, $node->level);
        }
    }

    /**
     * @test
     */
    public function can_get_tree()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $category = $this->repository->getById(1);
        $tree     = $this->repository->getTree(
            $category,
            [],
            [],
            null
        );

        // First level
        foreach ($tree['children'] as $node) {
            $this->assertEquals($category->id, $node->parentId);
            // nested level
            if (array_key_exists('children', $node)) {
                foreach ($node['children'] as $subnode) {
                    $this->assertEquals($node->id, $subnode->parentId);
                }
            }
        }
    }

    /**
     * @test
     */
    public function can_create_content_as_child()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $category        = $this->repository->getById(1);
        $categoryRoute   = $category->route->translations()->first();
        $content         = $this->repository->create(
            [
                'type'         => 'content',
                'parentId'     => $category->id,
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'Example content title'
                ]
            ]
        );
        $newContent      = $this->repository->getById($content->id);
        $newContentRoute = $newContent->route->translations()->first();
        // parentId
        $this->assertEquals($category->id, $newContent->parentId);
        // level
        $this->assertEquals($category->level + 1, $newContent->level);
        // path
        $this->assertEquals($category->path . $newContent->id . '/', $newContent->path);
        // route
        $this->assertEquals('en', $newContentRoute['langCode']);
        $this->assertEquals($categoryRoute->url . '/' . 'example-content-title', $newContentRoute['url']);
    }

    /**
     * @test
     */
    public function can_update_content_parent()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $content        = $this->repository->getById(1); // first root
        $oldContentPath = $content->path;
        $this->repository->update(
            $content,
            [
                'parentId' => 2, // second root
            ]
        );
        $updatedContent = $this->repository->getById($content->id);
        $newCategory    = $this->repository->getById($updatedContent->parentId);
        $this->assertNotEmpty($newCategory);
        $this->assertNotEquals($oldContentPath, $updatedContent->path);
        $this->assertEquals($newCategory->id, $updatedContent->parentId);
        $this->assertEquals($newCategory->path . $updatedContent->id . '/', $updatedContent->path);

        // Descendants
        $contents = $this->repository->getDescendants(
            $updatedContent,
            [],
            [],
            null
        );

        // Should contain updated path
        foreach ($contents as $key => $content) {
            $this->assertContains($updatedContent->path, $content->path);
        }
    }

    /**
     * @test
     */
    public function can_delete_content_with_children()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $content = $this->repository->getById(1);
        $this->repository->delete($content);
        // Content children has been removed?
        $this->assertEmpty($content->children()->get());
    }

    /*
    |--------------------------------------------------------------------------
    | END Tree tests
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | START List tests
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     */
    public function can_get_content_children_list()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);
        $category = $this->repository->getById(1);

        $contents = $this->repository->getChildren(
            $category,
            [],
            [],
            null
        );

        // parentId
        foreach ($contents as $content) {
            $this->assertEquals($category->id, $content->parentId);
        }
    }

    /**
     * @test
     */
    public function can_get_content_translations_list()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);
        $category = $this->repository->getById(1);
        // New translations
        for ($i = 0; $i < 3; $i++) {
            $this->repository->createTranslation(
                $category,
                [
                    'langCode' => 'pl',
                    'title'    => 'New example title',
                    'body'     => 'New example body'
                ]
            );
        }
        $contents = $this->repository->getTranslations(
            $category,
            [],
            [],
            null
        );
        // Number of new translations plus one for first translation
        $this->assertCount($i + 1, $contents);
        foreach ($contents as $content) {
            // parentId
            $this->assertEquals($category->id, $content->contentId);
        }
    }

    /**
     * @test
     */
    public function can_filter_contents_list()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $contents = $this->repository->getContents(
            [
                'type'     => ['value' => 'category', 'relation' => null],
                'isActive' => ['value' => true, 'relation' => null]
            ],
            [],
            null
        );

        foreach ($contents as $content) {
            $this->assertEquals('category', $content->type);
            $this->assertEquals(true, $content->isActive);
        }
    }

    /**
     * @test
     */
    public function can_sort_contents_list()
    {
        $this->repository->create(
            [
                'type'         => 'content',
                'weight'       => 0,
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'A title'
                ]
            ]
        );
        $this->repository->create(
            [
                'type'         => 'content',
                'weight'       => 1,
                'translations' => [
                    'langCode' => 'en',
                    'title'    => 'B title'
                ]
            ]
        );

        // Ascending
        $contents = $this->repository->getContents(
            [
                'lang' => ['value' => 'en', 'relation' => null]
            ],
            [
                'weight' => ['direction' => 'ASC', 'relation' => null],
                'title'  => ['direction' => 'ASC', 'relation' => 'translations'],
            ],
            null
        );
        // weight
        $this->assertEquals(0, $contents[0]['weight']);
        // translations title
        $this->assertEquals('A title', $contents[0]['translations'][0]['title']);

        // Descending
        $contents = $this->repository->getContents(
            [
                'lang' => ['value' => 'en', 'relation' => null]
            ],
            [
                'weight' => ['direction' => 'DESC', 'relation' => null],
                'title'  => ['direction' => 'DESC', 'relation' => 'translations'],
            ],
            null
        );
        // weight
        $this->assertEquals(1, $contents[0]['weight']);
        // translations title
        $this->assertEquals('B title', $contents[0]['translations'][0]['title']);
    }

    /**
     * @test
     * @expectedException Gzero\Core\Exception
     */
    public function it_checks_existence_of_lang_code_on_translations_join()
    {
        // Tree seeds
        $this->app['artisan']->call('db:seed', ['--class' => 'TestTreeSeeder']);

        $this->repository->getContents(
            [],
            ['weight' => ['direction' => 'DESC', 'relation' => null]],
            null
        );
    }

    /*
    |--------------------------------------------------------------------------
    | END List tests
    |--------------------------------------------------------------------------
    */
}