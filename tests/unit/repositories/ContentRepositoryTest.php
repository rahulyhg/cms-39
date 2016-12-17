<?php namespace functional;

use Gzero\Entity\Content;
use Gzero\Entity\File;
use Gzero\Entity\FileType;
use Gzero\Entity\User;
use Gzero\Repository\ContentRepository;
use Gzero\Repository\FileRepository;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once(__DIR__ . '/../../stub/TestSeeder.php');
require_once(__DIR__ . '/../../stub/TestTreeSeeder.php');

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
class ContentRepositoryTest extends \TestCase {

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var ContentRepository
     */
    protected $repository;

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * files directory
     */
    protected $filesDir;

    protected function _before()
    {
        // Start the Laravel application
        $this->startApplication();
        $this->fileRepository = new FileRepository(new File(), new FileType(), new Dispatcher());
        $this->repository     = new ContentRepository(new Content(), new Dispatcher(), $this->fileRepository);
        $this->filesDir       = __DIR__ . '/../../resources';
    }

    public function _after()
    {
        $dirName = config('gzero.upload.directory');
        if ($dirName) {
            Storage::deleteDirectory($dirName);
        }
        // Stop the Laravel application
        $this->stopApplication();
    }

    /*
    |--------------------------------------------------------------------------
    | START Content tests
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     */
    public function can_get_content_by_url()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
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
                'type'               => 'content',
                'is_on_home'         => true,
                'is_comment_allowed' => true,
                'is_promoted'        => true,
                'is_sticky'          => true,
                'is_active'          => true,
                'published_at'       => date('Y-m-d H:i:s'),
                'translations'       => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
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
        $this->assertEquals($content->is_on_home, $newContent->is_on_home);
        $this->assertEquals($content->is_comment_allowed, $newContent->is_comment_allowed);
        $this->assertEquals($content->is_promoted, $newContent->is_promoted);
        $this->assertEquals($content->is_sticky, $newContent->is_sticky);
        $this->assertEquals($content->is_active, $newContent->is_active);
        $this->assertEquals($content->published_at, $newContent->published_at);
        // Author
        $this->assertEquals($author->id, $newContent->author_id);
        $this->assertEquals($author->email, $newContentAuthor['email']);
        // Route
        $this->assertEquals('en', $newContentRoute['lang_code']);
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
                    'lang_code' => 'en',
                    'title'     => 'Example title'
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
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );
        $newContent       = $this->repository->getById($content->id);
        $translation      = $this->repository->createTranslation(
            $newContent,
            [
                'lang_code'      => 'en',
                'title'          => 'New example title',
                'body'           => 'New example body',
                'seo_title'       => 'New example seo_title',
                'seo_description' => 'New example seo_description'
            ]
        );
        $firstTranslation = $this->repository->getContentTranslationById($newContent, 1);
        $newTranslation   = $this->repository->getContentTranslationById($newContent, 2);
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($translation, $firstTranslation);
        // Check if previous translation are inactive
        $this->assertFalse((bool) $firstTranslation->is_active);
        // Check if a new translation has been added
        $this->assertEquals('en', $newTranslation->lang_code);
        $this->assertEquals('New example title', $newTranslation->title);
        $this->assertEquals('New example body', $newTranslation->body);
        $this->assertEquals('New example seo_title', $newTranslation->seo_title);
        $this->assertEquals('New example seo_description', $newTranslation->seo_description);
        $this->assertEquals($newContent->id, $newTranslation->content_id);
    }

    /**
     * @test
     */
    public function can_update_content()
    {
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'is_on_home'   => false,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->repository->update(
            $newContent,
            [
                'is_on_home' => true,
            ]
        );
        $updatedContent = $this->repository->getById($newContent->id);
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($newContent, $updatedContent);
        $this->assertEquals(true, $updatedContent->is_on_home);
    }

    /**
     * @test
     */
    public function can_delete_content()
    {
        $this->seed('TestSeeder');
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->assertNotSame($content, $newContent);
        $this->repository->delete($newContent);
        // Check if content has been removed
        $this->assertNull($this->repository->getById($newContent->id));
    }

    /**
     * @test
     */
    public function can_force_delete_content()
    {
        $this->seed('TestSeeder');
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );

        $otherContent = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Other title'
                ]
            ]
        );

        $newContent         = $this->repository->getById($content->id);
        $notRelatedContent  = $this->repository->getById($otherContent->id);
        $contentTranslation = $newContent->translations()->first();
        $contentRoute       = $newContent->route()->first();
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($otherContent, $notRelatedContent);
        $this->repository->forceDelete($newContent);
        // Get not related content
        $content2 = $this->repository->getById($notRelatedContent->id);

        // Check if content has been removed
        $this->assertNull($this->repository->getById($newContent->id));
        // Check if content translations has been removed
        $this->assertNull($this->repository->getTranslationById($contentTranslation->id));
        // Check if content route has been removed
        $this->assertNull($this->repository->getRouteById($contentRoute->id));
        // Check if not related content has not be removed
        $this->assertNotNull($content2);
        // Check if content route translation been removed
        $this->assertNull($this->repository->getByUrl('example-title', 'en'));
    }

    /**
     * @test
     */
    public function can_force_delete_soft_deleted_content()
    {
        $this->seed('TestSeeder');
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );

        $otherContent = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Other title'
                ]
            ]
        );

        $newContent         = $this->repository->getById($content->id);
        $notRelatedContent  = $this->repository->getById($otherContent->id);
        $contentTranslation = $newContent->translations()->first();
        $contentRoute       = $newContent->route()->first();
        $this->assertNotSame($content, $newContent);
        $this->assertNotSame($otherContent, $notRelatedContent);
        $this->repository->delete($newContent);
        $this->repository->forceDelete($newContent);
        // Get not related content
        $content2 = $this->repository->getById($notRelatedContent->id);
        // Check if content has been removed
        $this->assertNull($this->repository->getById($newContent->id));
        // Check if content translations has been removed
        $this->assertNull($this->repository->getTranslationById($contentTranslation->id));
        // Check if content route has been removed
        $this->assertNull($this->repository->getRouteById($contentRoute->id));
        // Check if not related content has not be removed
        $this->assertNotNull($content2);
        // Check if content route translation been removed
        $this->assertNull($this->repository->getByUrl('example-title', 'en'));
    }

    /**
     * @test
     */
    public function can_delete_content_translation()
    {
        $this->seed('TestSeeder');
        $withActive = false;
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                    'body'      => 'Example body'
                ]
            ]
        );
        $newContent = $this->repository->getById($content->id);
        $this->assertNotSame($content, $newContent);

        $this->repository->createTranslation(
            $content,
            [
                'lang_code' => 'en',
                'title'     => 'English translation 2'
            ]
        );
        $this->assertEquals($content->translations($withActive)->count(), 2);

        $this->repository->deleteTranslation($content->translations($withActive)->first());
        // Check if content translations has been removed
        $this->assertEquals($content->translations($withActive)->count(), 1);
    }

    /**
     * @test
     */
    public function can_create_content_with_same_title_as_one_of_soft_deleted_contents()
    {
        $this->seed('TestSeeder');
        $content1 = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                    'body'      => 'Example body'
                ]
            ]
        );

        $contentId1 = $content1->id;

        $this->repository->delete($content1);

        $content2 = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                    'body'      => 'Example body'
                ]
            ]
        );

        $content1 = $this->repository->getDeletedById($contentId1);
        $content1->restore();

        $this->assertEquals($content1->title, $content2->title);
        $this->assertNotEquals($content1->getUrl('en'), $content2->getUrl('en'));
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Content type doesn't exist
     */
    public function it_checks_existence_of_content_type()
    {
        $this->seed('TestSeeder');
        $this->repository->create(
            [
                'type'         => 'fakeType',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example category title'
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function it_checks_existence_of_content_url()
    {
        $this->seed('TestSeeder');
        $this->assertNull($this->repository->getByUrl('example-title', 'en'));
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Content type and translation is required
     */
    public function it_checks_existence_of_content_translation()
    {
        $this->seed('TestSeeder');
        $this->repository->create(['type' => 'category']);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Parent has not been translated in this language, translate it first!
     */
    public function it_checks_existence_of_parent_route_translation()
    {
        $this->seed('TestSeeder');
        $category    = $this->repository->create(
            [
                'type'         => 'category',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example category title'
                ]
            ]
        );
        $newCategory = $this->repository->getById($category->id);
        $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $newCategory->id,
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Parent node id: 1 doesn't exist
     */
    public function it_checks_existence_of_parent()
    {
        $this->seed('TestSeeder');
        $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => 1,
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Content type 'content' is not allowed for the parent type
     */
    public function it_checks_if_parent_is_proper_type()
    {
        $this->seed('TestSeeder');
        $content     = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example category title'
                ]
            ]
        );
        $newCategory = $this->repository->getById($content->id);
        $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $newCategory->id,
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_force_delete_one_content()
    {
        $this->seed('TestSeeder');
        $author   = User::find(1);
        $content  = $this->repository->create(
            [
                'type'               => 'content',
                'is_on_home'         => true,
                'is_comment_allowed' => true,
                'is_promoted'        => true,
                'is_sticky'          => true,
                'is_active'          => true,
                'published_at'       => date('Y-m-d H:i:s'),
                'translations'       => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ],
            $author
        );
        $content2 = $this->repository->create(
            [
                'type'               => 'content',
                'is_on_home'         => true,
                'is_comment_allowed' => true,
                'is_promoted'        => true,
                'is_sticky'          => true,
                'is_active'          => true,
                'published_at'       => date('Y-m-d H:i:s'),
                'translations'       => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ],
            $author
        );

        $this->repository->delete($content);
        $this->repository->delete($content2);

        $this->assertNull($this->repository->getById($content->id));
        $this->assertNull($this->repository->getById($content2->id));

        $this->repository->forceDelete($content);
        $this->assertNull($this->repository->getDeletedById($content->id));

        // Content2 should exist
        $this->assertNotNull($this->repository->getDeletedById($content2->id));
    }

    /**
     * @test
     */
    public function it_should_retrive_non_trashed_content()
    {
        $this->seed('TestSeeder');
        $content    = $this->repository->create(
            [
                'type'         => 'content',
                'is_active'    => 1,
                'translations' => [
                    'lang_code'      => 'en',
                    'title'          => 'Fake title',
                    'teaser'         => '<p>Super fake...</p>',
                    'body'           => '<p>Super fake body of some post!</p>',
                    'seo_title'       => 'fake-title',
                    'seo_description' => 'desc-demonstrate-fake',
                    'is_active'      => 1
                ]
            ]
        );
        $newContent = $this->repository->getByIdWithTrashed($content->id);
        $this->assertEquals($content->id, $newContent->id);
    }

    /**
     * @test
     */
    public function it_should_retrive_trashed_content()
    {
        $this->seed('TestSeeder');
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'is_active'    => 1,
                'translations' => [
                    'lang_code'      => 'en',
                    'title'          => 'Fake title',
                    'teaser'         => '<p>Super fake...</p>',
                    'body'           => '<p>Super fake body of some post!</p>',
                    'seo_title'       => 'fake-title',
                    'seo_description' => 'desc-demonstrate-fake',
                    'is_active'      => 1
                ]
            ]
        );
        $this->repository->delete($content);
        $trashedContent = $this->repository->getByIdWithTrashed($content->id);
        $this->assertEquals($content->id, $trashedContent->id);
    }

    /**
     * @test
     */
    public function it_should_not_retrive_force_deleted_content()
    {
        $this->seed('TestSeeder');
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'is_active'    => 1,
                'translations' => [
                    'lang_code'      => 'en',
                    'title'          => 'Fake title',
                    'teaser'         => '<p>Super fake...</p>',
                    'body'           => '<p>Super fake body of some post!</p>',
                    'seo_title'       => 'fake-title',
                    'seo_description' => 'desc-demonstrate-fake',
                    'is_active'      => 1
                ]
            ]
        );
        $this->repository->forceDelete($content);
        $this->assertNull($this->repository->getByIdWithTrashed($content->id));
    }

    /*
    |--------------------------------------------------------------------------
    | END Content tests
    |--------------------------------------------------------------------------
    */

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
        $this->seed('TestSeeder');
        $this->seed('TestTreeSeeder');

        $roots = $this->repository->getRoots(
            [],
            [],
            null
        );
        foreach ($roots as $node) {
            $this->assertNull($node->parent_id);
            $this->assertEquals(0, $node->level);
        }
    }

    /**
     * @test
     */
    public function can_get_tree()
    {
        // Tree seeds
        $this->seed('TestSeeder');
        $this->seed('TestTreeSeeder');

        $category = $this->repository->getById(1);
        $tree     = $this->repository->getTree(
            $category,
            [],
            [],
            null
        );

        // First level
        foreach ($tree['children'] as $node) {
            $this->assertEquals($category->id, $node->parent_id);
            // nested level
            if (array_key_exists('children', $node)) {
                foreach ($node['children'] as $subnode) {
                    $this->assertEquals($node->id, $subnode->parent_id);
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
        $this->seed('TestSeeder');
        $this->seed('TestTreeSeeder');

        $category        = $this->repository->getById(1);
        $categoryRoute   = $category->route->translations()->first();
        $content         = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example content title'
                ]
            ]
        );
        $newContent      = $this->repository->getById($content->id);
        $newContentRoute = $newContent->route->translations()->first();
        // parent_id
        $this->assertEquals($category->id, $newContent->parent_id);
        // level
        $this->assertEquals($category->level + 1, $newContent->level);
        // path
        $this->assertEquals($category->path . $newContent->id . '/', $newContent->path);
        // route
        $this->assertEquals('en', $newContentRoute['lang_code']);
        $this->assertEquals($categoryRoute->url . '/' . 'example-content-title', $newContentRoute['url']);
    }

    /**
     * @test
     */
    public function can_update_content_parent()
    {
        // Tree seeds
        $this->seed('TestSeeder');
        $this->seed('TestTreeSeeder');

        $category       = $this->repository->getById(1);
        $content        = $this->repository->getById(5);
        $oldContentPath = $content->path;
        $this->repository->update(
            $content,
            [
                'parent_id' => $category->id, // set parent id
            ]
        );
        $updatedContent = $this->repository->getById($content->id);
        $newCategory    = $this->repository->getById($updatedContent->parent_id);
        $this->assertNotEmpty($newCategory);
        $this->assertNotEquals($oldContentPath, $updatedContent->path);
        $this->assertEquals($newCategory->id, $updatedContent->parent_id);
        $this->assertEquals($newCategory->path . $updatedContent->id . '/', $updatedContent->path);
    }

    /**
     * @test
     */
    public function can_update_parent_for_category_without_children()
    {
        // Tree seeds
        $this->seed('TestSeeder');
        $this->seed('TestTreeSeeder');

        // Create new category without children
        $category        = $this->repository->create(
            [
                'type'         => 'category',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title'
                ]
            ]
        );
        $category        = $this->repository->getById($category->id);
        $parent          = $this->repository->getById(2);
        $oldCategoryPath = $category->path;
        $this->repository->update(
            $category,
            [
                'parent_id' => $parent->id, // set parent id
            ]
        );
        $updatedCategory = $this->repository->getById($category->id);
        $parentCategory  = $this->repository->getById($updatedCategory->parent_id);
        $this->assertNotEmpty($parentCategory);
        $this->assertNotEquals($oldCategoryPath, $updatedCategory->path);
        $this->assertEquals($parentCategory->id, $updatedCategory->parent_id);
        $this->assertEquals($parentCategory->path . $updatedCategory->id . '/', $updatedCategory->path);
    }

    /**
     * @test
     */
    public function can_create_route()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        // Single content
        $singleContent = $this->repository->getById(2);

        // Crate single route
        $this->repository->createRoute($singleContent, 'en', 'Single content url');
        $updatedContent      = $this->repository->getById($singleContent->id);
        $updatedContentRoute = $updatedContent->route->translations()->first();

        // Check single route
        $this->assertEquals('en', $updatedContentRoute['lang_code']);
        $this->assertEquals('single-content-url', $updatedContentRoute['url']);

        // Nested content
        $category      = $this->repository->getById(1);
        $categoryRoute = $category->route->translations()->first();
        $nestedContent = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example content title'
                ]
            ]
        );

        // Crate nested route
        $newContent = $this->repository->getById($nestedContent->id);
        $this->repository->createRoute($newContent, 'en', 'Nested content url');
        $updatedContent      = $this->repository->getById($nestedContent->id);
        $updatedContentRoute = $updatedContent->route->translations()->first();

        // Check nested route
        $this->assertEquals('en', $updatedContentRoute['lang_code']);
        $this->assertEquals($categoryRoute->url . '/' . 'nested-content-url', $updatedContentRoute['url']);

        // Crate unique route
        $this->repository->createRoute($newContent, 'en', 'Nested content url');
        $updatedContent      = $this->repository->getById($nestedContent->id);
        $updatedContentRoute = $updatedContent->route->translations()->first();

        // Check unique route
        $this->assertEquals('en', $updatedContentRoute['lang_code']);
        $this->assertEquals($categoryRoute->url . '/' . 'nested-content-url-1', $updatedContentRoute['url']);
    }

    /**
     * @test
     */
    public function can_get_list_of_deleted_contents()
    {
        $category = $this->repository->create(
            [
                'type'         => 'category',
                'weight'       => 3,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'A title'
                ]
            ]
        );

        $content1 = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 2,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'A title'
                ]
            ]
        );

        $content2 = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 0,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'B title'
                ]
            ]
        );

        $contents = $this->repository->getContents([], [['weight', 'ASC']], null, null);

        $this->assertEquals(3, $contents->count());

        $this->assertEquals($contents[0]->weight, 0);
        $this->assertEquals($contents[0]->level, 1);
        $this->assertEquals($contents[1]->weight, 2);
        $this->assertEquals($contents[1]->level, 1);
        $this->assertEquals($contents[2]->weight, 3);
        $this->assertEquals($contents[2]->level, 0);

        $this->repository->delete($content1);
        $this->repository->delete($content2);
        $this->repository->delete($category);

        $contentsAfterDelete = $this->repository->getContents([], [['weight', 'ASC']], null, null);
        $deletedContents     = $this->repository->getDeletedContents([], [['weight', 'ASC']], null, null);

        $this->assertEquals(0, $contentsAfterDelete->count());
        $this->assertEquals(3, $deletedContents->count());

        $this->assertEquals($deletedContents[0]->weight, 0);
        $this->assertEquals($deletedContents[0]->level, 1);
        $this->assertEquals($deletedContents[1]->weight, 2);
        $this->assertEquals($deletedContents[1]->level, 1);
        $this->assertEquals($deletedContents[2]->weight, 3);
        $this->assertEquals($deletedContents[2]->level, 0);

    }

    /**
     * @test
     */
    public function can_get_list_of_deleted_contents_tree()
    {
        $category = $this->repository->create(
            [
                'type'         => 'category',
                'weight'       => 3,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'A title'
                ]
            ]
        );

        $content1 = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 2,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'A title'
                ]
            ]
        );

        $content2 = $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 0,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'B title'
                ]
            ]
        );

        $contents = $this->repository->getContentsByLevel([], [['weight', 'ASC']], null, null);

        $this->assertEquals(3, $contents->count());

        $this->assertEquals($contents[0]->weight, 3);
        $this->assertEquals($contents[0]->level, 0);
        $this->assertEquals($contents[1]->weight, 0);
        $this->assertEquals($contents[1]->level, 1);
        $this->assertEquals($contents[2]->weight, 2);
        $this->assertEquals($contents[2]->level, 1);

        $this->repository->delete($content1);
        $this->repository->delete($content2);
        $this->repository->delete($category);

        $contentsAfterDelete = $this->repository->getContentsByLevel([], [['weight', 'ASC']], null, null);
        $deletedContents     = $this->repository->getDeletedContentsByLevel([], [['weight', 'ASC']], null, null);

        $this->assertEquals(0, $contentsAfterDelete->count());
        $this->assertEquals(3, $deletedContents->count());

        $this->assertEquals($deletedContents[0]->weight, 3);
        $this->assertEquals($deletedContents[0]->level, 0);
        $this->assertEquals($deletedContents[1]->weight, 0);
        $this->assertEquals($deletedContents[1]->level, 1);
        $this->assertEquals($deletedContents[2]->weight, 2);
        $this->assertEquals($deletedContents[2]->level, 1);

    }


    /**
     * @test
     */
    public function can_delete_content_with_children()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $content = $this->repository->getById(1);
        $this->repository->delete($content);
        // Content children has been removed?
        $this->assertEmpty($content->children()->get());
    }

    /**
     * @test
     */
    public function can_force_delete_content_with_children()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $content = $this->repository->getById(1);
        $this->repository->forceDelete($content);
        // Content children has been removed?
        $this->assertEmpty($content->children()->get());
    }

    /**
     * @test
     */
    public function can_force_delete_soft_deleted_content_with_children()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $content = $this->repository->getById(1);
        $this->repository->delete($content);
        $this->repository->forceDelete($content);
        // Content children has been removed?
        $this->assertEmpty($content->children()->get());
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryException
     * @expectedExceptionMessage You cannot change parent of not empty category
     */
    public function it_does_not_allow_to_update_parent_for_category_with_children()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        // Get category with children
        $category = $this->repository->getById(1);
        $parent   = $this->repository->getById(2);

        // Update category parent
        $this->repository->update(
            $category,
            [
                'parent_id' => $parent->id,
            ]
        );
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
        $this->seed('TestTreeSeeder');
        $category = $this->repository->getById(1);

        $contents = $this->repository->getChildren(
            $category,
            [],
            [],
            null
        );

        // parent_id
        foreach ($contents as $content) {
            $this->assertEquals($category->id, $content->parent_id);
        }
    }

    /**
     * @test
     */
    public function can_get_content_translations_list()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');
        $category = $this->repository->getById(1);
        // New translations
        for ($i = 0; $i < 3; $i++) {
            $this->repository->createTranslation(
                $category,
                [
                    'lang_code' => 'pl',
                    'title'     => 'New example title',
                    'body'      => 'New example body'
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
            // parent_id
            $this->assertEquals($category->id, $content->content_id);
        }
    }

    /**
     * @test
     */
    public function can_filter_contents_list()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $contents = $this->repository->getContents(
            [
                ['type', '=', 'category'],
                ['is_active', '=', true]
            ],
            [],
            null
        );

        foreach ($contents as $content) {
            $this->assertEquals('category', $content->type);
            $this->assertEquals(true, $content->is_active);
        }

        $contents = $this->repository->getContentsByLevel(
            [
                ['type', '=', 'category'],
                ['is_active', '=', true]
            ],
            [],
            null
        );

        foreach ($contents as $content) {
            $this->assertEquals('category', $content->type);
            $this->assertEquals(true, $content->is_active);
        }
    }

    /**
     * @test
     */
    public function can_sort_contents_list()
    {
        $category = $this->repository->create(
            [
                'type'         => 'category',
                'weight'       => 10,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'C title'
                ]
            ]
        );
        $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 0,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'A title'
                ]
            ]
        );
        $this->repository->create(
            [
                'type'         => 'content',
                'parent_id'    => $category->id,
                'weight'       => 1,
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'B title'
                ]
            ]
        );

        // Ascending
        $contents = $this->repository->getContents(
            [
                ['translations.lang', '=', 'en']
            ],
            [
                ['weight', 'ASC'],
                ['translations.title', 'ASC'],
            ],
            null
        );

        $this->assertEquals(3, $contents->count());
        $this->assertEquals(0, $contents[0]->weight);
        $this->assertEquals(1, $contents[1]->weight);
        $this->assertEquals(10, $contents[2]->weight);
        // Translations title
        $this->assertEquals('A title', $contents[0]->translations[0]->title);
        $this->assertEquals('B title', $contents[1]->translations[0]->title);
        $this->assertEquals('C title', $contents[2]->translations[0]->title);

        // Descending
        $contents = $this->repository->getContents(
            [
                ['translations.lang', '=', 'en']
            ],
            [
                ['weight', 'DESC'],
                ['translations.title', 'DESC'],
            ],
            null
        );

        $this->assertEquals(3, $contents->count());
        $this->assertEquals(10, $contents[0]->weight);
        $this->assertEquals(1, $contents[1]->weight);
        $this->assertEquals(0, $contents[2]->weight);

        $this->assertEquals('C title', $contents[0]->translations[0]->title);
        $this->assertEquals('B title', $contents[1]->translations[0]->title);
        $this->assertEquals('A title', $contents[2]->translations[0]->title);

        // Ascending
        $contents = $this->repository->getContentsByLevel(
            [
                ['translations.lang', '=', 'en']
            ],
            [
                ['weight', 'ASC'],
                ['translations.title', 'ASC'],
            ],
            null
        );

        $this->assertEquals(3, $contents->count());
        $this->assertEquals(10, $contents[0]->weight);
        $this->assertEquals(0, $contents[0]->level);
        $this->assertEquals(0, $contents[1]->weight);
        $this->assertEquals(1, $contents[1]->level);
        $this->assertEquals(1, $contents[2]->weight);
        $this->assertEquals(1, $contents[1]->level);

        $this->assertEquals('C title', $contents[0]->translations[0]->title);
        $this->assertEquals('A title', $contents[1]->translations[0]->title);
        $this->assertEquals('B title', $contents[2]->translations[0]->title);

        // Descending
        $contents = $this->repository->getContentsByLevel(
            [
                ['translations.lang', '=', 'en']
            ],
            [
                ['weight', 'DESC'],
                ['translations.title', 'DESC'],
            ],
            null
        );

        $this->assertEquals(3, $contents->count());
        $this->assertEquals(10, $contents[0]->weight);
        $this->assertEquals(0, $contents[0]->level);
        $this->assertEquals(1, $contents[1]->weight);
        $this->assertEquals(1, $contents[1]->level);
        $this->assertEquals(0, $contents[2]->weight);
        $this->assertEquals(1, $contents[2]->level);

        $this->assertEquals('C title', $contents[0]->translations[0]->title);
        $this->assertEquals('B title', $contents[1]->translations[0]->title);
        $this->assertEquals('A title', $contents[2]->translations[0]->title);

    }

    /*
    |--------------------------------------------------------------------------
    | END Lists tests
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | START Translations tests
    |--------------------------------------------------------------------------
    */

    /**
     * @test                     Change tree seeder to seeder
     * @expectedException \Gzero\Repository\RepositoryException
     * @expectedExceptionMessage Error: 'lang' criteria is required
     */
    public function it_checks_existence_of_lang_code_on_translations_join()
    {
        // Tree seeds
        $this->seed('TestSeeder');

        $this->repository->getContents([], [['translations.title', 'DESC']], null);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryException
     * @expectedExceptionMessage Error: 'lang' criteria is required
     */
    public function it_checks_existence_of_lang_code_on_translations_join_tree()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $this->repository->getContentsByLevel(
            [],
            [['translations.title', 'DESC']],
            null
        );
    }

    /**
     * @test
     */
    public function it_doesnt_check_existence_of_lang_code_for_core_order_by_params()
    {
        // Tree seeds
        $this->seed('TestTreeSeeder');

        $nodes = $this->repository->getContents(
            [],
            [['weight', 'DESC']],
            null
        );
        $this->assertNotEmpty($nodes);

        $nodes = $this->repository->getContentsByLevel(
            [],
            [['weight', 'DESC']],
            null
        );
        $this->assertNotEmpty($nodes);
    }

    /**
     * @test
     */
    public function can_get_ancestor()
    {

        $category1 = $this->repository->create(
            [
                'type'         => 'category',
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );

        $category2 = $this->repository->create(
            [
                'type'         => 'category',
                'parent_id'    => $category1->id,
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );

        $category3 = $this->repository->create(
            [
                'type'         => 'category',
                'parent_id'    => $category2->id,
                'translations' => [
                    'lang_code' => 'pl',
                    'title'     => 'Example content title'
                ]
            ]
        );

        $parents = $this->repository->getAncestors($category3, []);

        $this->assertEquals($parents[0]->id, $category1->id);
        $this->assertEquals($parents[1]->id, $category2->id);

    }

    /**
     * @test
     */
    public function it_does_not_duplicate_content_when_translation_added()
    {
        $author  = User::find(1);
        $content = $this->repository->create(
            [
                'type'               => 'content',
                'is_on_home'         => false,
                'is_comment_allowed' => false,
                'is_promoted'        => false,
                'is_sticky'          => false,
                'is_active'          => true,
                'published_at'       => date('Y-m-d H:i:s'),
                'translations'       => [
                    'lang_code' => 'en',
                    'title'     => 'English translation 1'
                ]
            ],
            $author
        );
        $this->assertInstanceOf('Gzero\Entity\Content', $content);

        $translation = $this->repository->createTranslation(
            $content,
            [
                'lang_code' => 'en',
                'title'     => 'English translation 2'
            ]
        );
        $this->assertInstanceOf('Gzero\Entity\ContentTranslation', $translation);

        $translatedContent = $this->repository->getContents(
            [
                ['lang', '=', 'en'],
                ['type', '=', 'content']
            ],
            [],
            1,
            20
        );
        $this->assertEquals(1, $translatedContent->count());

        $translatedContent = $this->repository->getContentsByLevel(
            [
                ['lang', '=', 'en'],
                ['type', '=', 'content']
            ],
            [],
            1,
            20
        );
        $this->assertEquals(1, $translatedContent->count());

    }

    /**
     * @test
     */
    public function it_does_not_allow_to_delete_active_translation()
    {
        $author  = User::find(1);
        $content = $this->repository->create(
            [
                'type'               => 'content',
                'is_on_home'         => false,
                'is_comment_allowed' => false,
                'is_promoted'        => false,
                'is_sticky'          => false,
                'is_active'          => true,
                'published_at'       => date('Y-m-d H:i:s'),
                'translations'       => [
                    'lang_code' => 'en',
                    'title'     => 'English translation 1'
                ]
            ],
            $author
        );
        $this->assertInstanceOf('Gzero\Entity\Content', $content);

        $translations = $this->repository->getTranslations($content, []);
        $translation  = $translations->first();
        $this->assertInstanceOf('Gzero\Entity\ContentTranslation', $translation);
        $this->assertEquals($translation->is_active, 1);

        $this->setExpectedException('Gzero\Repository\RepositoryException');
        $this->repository->deleteTranslation($translation);
    }

    /**
     * @test
     */
    public function it_creates_new_route_only_for_new_content()
    {
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Add new content translation
        $this->repository->createTranslation(
            $content,
            [
                'lang_code' => 'en',
                'title'     => 'Modified example title',
            ]
        );

        $newContent      = $this->repository->getById($content->id);
        $newContentRoute = $newContent->route->translations()->first();
        // Route translation should not be changed
        $this->assertEquals('en', $newContentRoute['lang_code']);
        $this->assertEquals('example-title', $newContentRoute['url']);
    }

    /*
    |--------------------------------------------------------------------------
    | END Translations tests
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | START Files tests
    |--------------------------------------------------------------------------
    */

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage Please provide content related file id
     */
    public function it_checks_relation_of_related_file()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $file         = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => true,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        $this->repository->update($content, ['file_id' => $file->id]);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage File (id: 1) does not exist
     */
    public function it_checks_existence_of_files_to_add()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        $this->repository->addFiles($content, [1]);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage You must provide the files in order to add them to the content
     */
    public function it_checks_for_empty_array_of_files_to_add()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        $this->repository->addFiles($content, []);
    }

    /**
     * @test
     */
    public function can_get_single_file()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $file         = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => true,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        // Assign files
        $this->repository->addFiles($content, [$file->id]);
        $this->repository->update($content, ['file_id' => $file->id]);
        $relatedFile = $this->repository->getContentFileById($content, $content->file_id);

        $this->assertNotEmpty($relatedFile);
        $this->assertEquals($content->file_id, $file->id);
        $this->assertEquals($file->name, $relatedFile->name);
        $this->assertEquals($file->type, $relatedFile->type);
        $this->assertEquals($file->is_active, $relatedFile->is_active);
        $this->assertEquals($file->extension, $relatedFile->extension);
        $this->assertEquals($file->mime_type, $relatedFile->mime_type);
        $this->assertEquals($file->info, $relatedFile->info);
        $this->assertEquals($file->translations[0]->lang_code, $relatedFile->translations[0]->lang_code);
        $this->assertEquals($file->translations[0]->title, $relatedFile->translations[0]->title);
        $this->assertEquals($file->translations[0]->description, $relatedFile->translations[0]->description);
    }

    /**
     * @test
     */
    public function can_add_content_related_file()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $file         = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => true,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        // Assign files
        $this->repository->addFiles($content, [$file->id]);
        $this->repository->update($content, ['file_id' => $file->id]);
        $files = $content->files()->get();

        $this->assertNotEmpty($files);
        $this->assertEquals($content->file_id, $file->id);
        $this->assertEquals($file->name, $files[0]->name);
        $this->assertEquals($file->type, $files[0]->type);
        $this->assertEquals($file->is_active, $files[0]->is_active);
        $this->assertEquals($file->extension, $files[0]->extension);
        $this->assertEquals($file->mime_type, $files[0]->mime_type);
        $this->assertEquals($file->info, $files[0]->info);
        $this->assertEquals($file->translations[0]->lang_code, $files[0]->translations[0]->lang_code);
        $this->assertEquals($file->translations[0]->title, $files[0]->translations[0]->title);
        $this->assertEquals($file->translations[0]->description, $files[0]->translations[0]->description);
    }

    /**
     * @test
     */
    public function can_add_content_files()
    {
        $this->seed('TestSeeder');
        $fileIds = [];

        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        for ($i = 0; $i < 3; $i++) {
            $file = $this->fileRepository->create(
                [
                    'type'         => 'image',
                    'is_active'    => true,
                    'info'         => ['key' => 'value'],
                    'translations' => [
                        'lang_code'   => 'en',
                        'title'       => 'Example file title',
                        'description' => 'Example file description'
                    ]
                ],
                $uploadedFile,
                $author
            );

            $fileIds[] = $file->id;
        }

        // Assign files
        $this->repository->addFiles($content, $fileIds);
        $files = $content->files()->get();

        $this->assertNotEmpty($files);
        $this->assertEquals('example', $files[0]->name);
        $this->assertEquals('example-1', $files[1]->name);
        $this->assertEquals('example-2', $files[2]->name);

        foreach ($files as $index => $file) {
            $this->assertEquals($fileIds[$index], $file->id);
            $this->assertEquals('image', $file->type);
            $this->assertEquals(true, $file->is_active);
            $this->assertEquals('png', $file->extension);
            $this->assertEquals('image/png', $file->mime_type);
            $this->assertEquals(['key' => 'value'], $file->info);
            $this->assertEquals('en', $file->translations[0]->lang_code);
            $this->assertEquals('Example file title', $file->translations[0]->title);
            $this->assertEquals('Example file description', $file->translations[0]->description);
        }
    }

    /**
     * @test
     */
    public function can_add_content_files_without_removing_already_added()
    {
        $this->seed('TestSeeder');
        $fileIds = [];

        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        for ($i = 0; $i < 6; $i++) {
            $file = $this->fileRepository->create(
                [
                    'type'         => 'image',
                    'is_active'    => true,
                    'info'         => ['key' => 'value'],
                    'translations' => [
                        'lang_code'   => 'en',
                        'title'       => 'Example file title',
                        'description' => 'Example file description'
                    ]
                ],
                $uploadedFile,
                $author
            );

            $fileIds[] = $file->id;
        }

        // Assign first 3 files
        $this->repository->addFiles($content, [$fileIds[0], $fileIds[1], $fileIds[2]]);
        // Assign next 3 files
        $this->repository->addFiles($content, [$fileIds[3], $fileIds[4], $fileIds[5]]);
        $files = $content->files()->get();

        $this->assertNotEmpty($files);
        $this->assertEquals('example', $files[0]->name);
        $this->assertEquals('example-1', $files[1]->name);
        $this->assertEquals('example-2', $files[2]->name);
        $this->assertEquals('example-3', $files[3]->name);
        $this->assertEquals('example-4', $files[4]->name);
        $this->assertEquals('example-5', $files[5]->name);

        foreach ($files as $index => $file) {
            $this->assertEquals($fileIds[$index], $file->id);
            $this->assertEquals('image', $file->type);
            $this->assertEquals(true, $file->is_active);
            $this->assertEquals('png', $file->extension);
            $this->assertEquals('image/png', $file->mime_type);
            $this->assertEquals(['key' => 'value'], $file->info);
            $this->assertEquals('en', $file->translations[0]->lang_code);
            $this->assertEquals('Example file title', $file->translations[0]->title);
            $this->assertEquals('Example file description', $file->translations[0]->description);
        }
    }

    /**
     * @test
     */
    public function can_sort_content_files_list()
    {
        $this->seed('TestSeeder');
        $fileIds = [];

        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        for ($i = 0; $i < 3; $i++) {
            $file = $this->fileRepository->create(
                [
                    'type'         => 'image',
                    'is_active'    => true,
                    'info'         => ['key' => 'value'],
                    'translations' => [
                        'lang_code'   => 'en',
                        'title'       => 'Example file title ' . $i,
                        'description' => 'Example file description'
                    ]
                ],
                $uploadedFile,
                $author
            );

            $fileIds[] = $file->id;
        }

        // Assign files
        $this->repository->addFiles($content, $fileIds);
        $files = $this->repository->getFiles(
            $content,
            [['translations.lang', '=', 'en']],
            [['translations.title', 'ASC'], ['pivot.weight', 'DESC']]
        );

        $this->assertNotEmpty($files);
        $this->assertEquals('example', $files[0]->name);
        $this->assertEquals('Example file title 0', $files[0]->translations[0]->title);
        $this->assertEquals('example-1', $files[1]->name);
        $this->assertEquals('Example file title 1', $files[1]->translations[0]->title);
        $this->assertEquals('example-2', $files[2]->name);
        $this->assertEquals('Example file title 2', $files[2]->translations[0]->title);
    }

    /**
     * @test
     */
    public function can_filter_content_files_list()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $file         = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => false,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        // Assign files
        $this->repository->addFiles($content, [$file->id]);
        $files = $this->repository->getFiles(
            $content,
            [['is_active', '=', true]],
            []
        );

        $this->assertEmpty($files);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage You must provide the file in order to update it
     */
    public function it_checks_for_empty_file_id_to_update()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        $this->repository->updateFile($content, null, []);
    }

    /**
     * @test
     */
    public function can_update_content_file()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $file         = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => true,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        $this->repository->addFiles($content, [$file->id]);
        $this->repository->updateFile($content, $file->id, ['weight' => 2]);
        $files = $this->repository->getFiles($content, [], []);

        $this->assertNotEmpty($files);
        $this->assertEquals(2, $files[0]->pivot->weight);
    }

    /**
     * @test
     * @expectedException \Gzero\Repository\RepositoryValidationException
     * @expectedExceptionMessage You must provide the files in order to remove them from the content
     */
    public function it_checks_for_empty_array_of_files_to_remove()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        $this->repository->removeFiles($content, []);
    }

    /**
     * @test
     */
    public function can_remove_content_files()
    {
        $this->seed('TestSeeder');
        $fileIds = [];

        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        for ($i = 0; $i < 3; $i++) {
            $file = $this->fileRepository->create(
                [
                    'type'         => 'image',
                    'is_active'    => true,
                    'info'         => ['key' => 'value'],
                    'translations' => [
                        'lang_code'   => 'en',
                        'title'       => 'Example file title',
                        'description' => 'Example file description'
                    ]
                ],
                $uploadedFile,
                $author
            );

            $fileIds[] = $file->id;
        }

        $this->repository->addFiles($content, $fileIds);
        $this->repository->removeFiles($content, $fileIds);
        $files = $content->files()->get();

        $this->assertEmpty($files);
    }

    /**
     * @test
     */
    public function can_remove_content_related_file_when_removing_files()
    {
        $this->seed('TestSeeder');
        // Create new content with first translation
        $content = $this->repository->create(
            [
                'type'         => 'content',
                'translations' => [
                    'lang_code' => 'en',
                    'title'     => 'Example title',
                ]
            ]
        );

        // Create files
        $uploadedFile = $this->getExampleImage();
        $author       = User::find(1);
        $relatedFile  = $this->fileRepository->create(
            [
                'type'         => 'image',
                'is_active'    => true,
                'info'         => ['key' => 'value'],
                'translations' => [
                    'lang_code'   => 'en',
                    'title'       => 'Example file title',
                    'description' => 'Example file description'
                ]
            ],
            $uploadedFile,
            $author
        );

        $fileIds = [$relatedFile->id];

        for ($i = 0; $i < 3; $i++) {
            $file = $this->fileRepository->create(
                [
                    'type'         => 'image',
                    'is_active'    => true,
                    'info'         => ['key' => 'value'],
                    'translations' => [
                        'lang_code'   => 'en',
                        'title'       => 'Example file title',
                        'description' => 'Example file description'
                    ]
                ],
                $uploadedFile,
                $author
            );

            $fileIds[] = $file->id;
        }

        $this->repository->addFiles($content, $fileIds);

        $this->repository->update($content, ['file_id' => $relatedFile->id]);
        $this->repository->removeFiles($content, $fileIds);
        $files = $content->files()->get();

        $this->assertEmpty($files);
        $this->assertNull($content->file_id);
    }

    /*
    |--------------------------------------------------------------------------
    | END Files tests
    |--------------------------------------------------------------------------
    */

    private function getExampleImage()
    {
        return new UploadedFile($this->filesDir . '/example.png', 'example.png', 'image/jpeg', null, null, true);
    }
}