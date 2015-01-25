<?php namespace Gzero\Entity\Presenter;

use Carbon\Carbon;
use Robbo\Presenter\Presenter;

/**
 * This file is part of the GZERO CMS package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Class Content
 *
 * @package    Gzero\Entity\Presenter
 * @author     Adrian Skierniewski <adrian.skierniewski@gmail.com>
 * @copyright  Copyright (c) 2015, Adrian Skierniewski
 */
class ContentPresenter extends Presenter {

    /**
     * This function get single translation
     *
     * @param string $langCode LangCode
     *
     * @return string
     */
    public function translation($langCode)
    {
        return $this->translations->filter(
            function ($translation) use ($langCode) {
                return $translation->langCode === $langCode;
            }
        )->first();
    }

    /**
     * This function get single route translation
     *
     * @param string $langCode LangCode
     *
     * @return string
     */
    public function routeTranslation($langCode)
    {
        return $this->route->translations->filter(
            function ($translation) use ($langCode) {
                return $translation->langCode === $langCode;
            }
        )->first();
    }

    /**
     * This function returns formatted publish date
     *
     * @return string
     */
    public function publishDate()
    {
        $dt = new Carbon();
        return $dt->parse($this->publishedAt)->format('d-m-Y - H:s');
    }

    /**
     * This function returns author first and last name
     *
     * @return string
     */
    public function authorName()
    {
        return $this->author->firstName . ' ' . $this->author->lastName;
    }

    /**
     * This function returns the star rating
     *
     * @return string html containing star icons
     */
    public function ratingStars()
    {
        $html = [];
        for ($i = 0; $i < 5; $i++) {
            if ($i < $this->rating && $this->rating > 0) {
                $html[] = '<i class="glyphicon glyphicon-star"></i> ';
            } else {
                $html[] = '<i class="glyphicon glyphicon-star-empty"></i> ';
            }
        }
        return implode('', $html);
    }
}
