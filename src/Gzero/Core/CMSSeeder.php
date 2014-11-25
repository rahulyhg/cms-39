<?php namespace Gzero\Core;

use Faker\Factory;
use Gzero\Entity\Content;
use Gzero\Entity\ContentTranslation;
use Gzero\Entity\Lang;
use Gzero\Entity\Route;
use Gzero\Entity\RouteTranslation;
use Gzero\Entity\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class CMSSeeder
 *
 * @package    Gzero\Core
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2014, Adrian Skierniewski
 * @SuppressWarnings("PHPMD")
 */
class CMSSeeder extends Seeder {

    /**
     * This function run all seeds
     *
     * @return void
     * @SuppressWarnings("PHPMD")
     */
    public function run()
    {
        $faker       = Factory::create();
        $langs       = [];
        $langs['en'] = Lang::find('en');
        if (empty($langs['en'])) {
            $langs['en'] = new Lang(
                [
                    'code'      => 'en',
                    'i18n'      => 'en_US',
                    'isEnabled' => 1,
                    'isDefault' => true
                ]
            );
            $langs['en']->save();
        }

        $langs['pl'] = Lang::find('pl');
        if (empty($langs['pl'])) {
            $langs['pl'] = new Lang(
                [
                    'code'      => 'pl',
                    'i18n'      => 'pl_PL',
                    'isEnabled' => 1
                ]
            );
            $langs['pl']->save();
        }

        $contents = [];
        for ($i = 0; $i < 20; $i++) {
            $contents[$i]         = new Content(['isActive' => (bool) rand(0, 1)]);
            $contents[$i]->weight = rand(0, 10);
            $contents[$i]->save();
            $route = new Route(['isActive' => 1]);
            $contents[$i]->route()->save($route);
            foreach ($langs as $key => $value) {
                $translation           = new ContentTranslation(['langCode' => $key]);
                $translation->title    = $faker->sentence(5);
                $translation->body     = $faker->text(255);
                $translation->isActive = true;
                $contents[$i]->translations()->save($translation);
                $routeTranslation = new RouteTranslation(
                    [
                        'langCode' => $key,
                        'url'      => $faker->word,
                        'isActive' => true
                    ]
                );
                $route->translations()->save($routeTranslation);
            }
        }
        $contents[17]->setChildOf($contents[0]);
        $contents[18]->setChildOf($contents[0]);
        $contents[19]->setChildOf($contents[1]);
        // Create user
        $user = User::find(1);
        if (!$user) {
            User::create(
                [
                    'email'     => 'a@a.pl',
                    'firstName' => 'John',
                    'lastName'  => 'Doe',
                    'password'  => Hash::make('test')

                ]
            );
        }
    }
}
