<?php namespace Gzero\Cms\Http\Resources;

use Gzero\Core\Http\Resources\Route;
use Gzero\Core\Http\Resources\File;
use Illuminate\Http\Resources\Json\Resource;

/**
 * @SWG\Definition(
 *   definition="Content",
 *   type="object",
 *   required={},
 *   @SWG\Property(
 *     property="parent_id",
 *     type="int",
 *     example="2"
 *   ),
 *   @SWG\Property(
 *     property="author_id",
 *     type="number",
 *     example="10"
 *   ),
 *   @SWG\Property(
 *     property="level",
 *     type="int",
 *     example="1"
 *   ),
 *   @SWG\Property(
 *     property="type",
 *     type="string",
 *     example="content"
 *   ),
 *   @SWG\Property(
 *     property="theme",
 *     type="string",
 *     example="is-content"
 *   ),
 *   @SWG\Property(
 *     property="weight",
 *     type="number",
 *     example="100"
 *   ),
 *   @SWG\Property(
 *     property="rating",
 *     type="number",
 *     example="10"
 *   ),
 *   @SWG\Property(
 *     property="is_on_home",
 *     type="boolean",
 *     example="true"
 *   ),
 *   @SWG\Property(
 *     property="is_comment_allowed",
 *     type="boolean",
 *     example="true"
 *   ),
 *   @SWG\Property(
 *     property="is_promoted",
 *     type="boolean",
 *     example="true"
 *   ),
 *   @SWG\Property(
 *     property="is_sticky",
 *     type="boolean",
 *     example="true"
 *   ),
 *   @SWG\Property(
 *     property="path",
 *     type="array",
 *     description="Represents hierarchy in tree by id's",
 *     example="[1,2,3]",
 *     @SWG\Items(type="number")
 *   ),
 *   @SWG\Property(
 *     property="published_at",
 *     type="string",
 *     format="date-time"
 *   ),
 *   @SWG\Property(
 *     property="created_at",
 *     type="string",
 *     format="date-time"
 *   ),
 *   @SWG\Property(
 *     property="updated_at",
 *     type="string",
 *     format="date-time"
 *   ),
 *   @SWG\Property(
 *     property="routes",
 *     type="array",
 *     @SWG\Items(ref="#/definitions/Route")
 *   ),
 *   @SWG\Property(
 *     property="translations",
 *     type="array",
 *     @SWG\Items(ref="#/definitions/ContentTranslation")
 *   )
 * )
 */
class Content extends Resource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                 => (int) $this->id,
            'parent_id'          => $this->parent_id,
            'author_id'          => $this->author_id,
            'level'              => $this->level,
            'type'               => $this->whenLoaded('type', function () {
                return $this->type->name;
            }),
            'thumbnail'          => $this->whenLoaded('thumb', function () {
                return new File($this->thumb);
            }),
            'theme'              => $this->theme,
            'weight'             => $this->weight,
            'rating'             => $this->rating,
            'is_on_home'         => $this->is_on_home,
            'is_comment_allowed' => $this->is_comment_allowed,
            'is_promoted'        => $this->is_promoted,
            'is_sticky'          => $this->is_sticky,
            'path'               => $this->buildPath($this->path),
            'published_at'       => optional(dateTimeToRequestTimezone($this->published_at))->toIso8601String(),
            'created_at'         => dateTimeToRequestTimezone($this->created_at)->toIso8601String(),
            'updated_at'         => dateTimeToRequestTimezone($this->updated_at)->toIso8601String(),
            'routes'             => $this->whenLoaded('routes', function () {
                return Route::collection($this->routes);
            }),
            'translations'       => ContentTranslation::collection($this->whenLoaded('translations')),
            'children'           => $this->when(!empty($this->children), function () {
                return Content::collection($this->children);
            }),
        ];
    }

    /**
     * Returns array of path ids as integers
     *
     * @param Content $path path to explode
     *
     * @return array extracted path
     */
    private function buildPath($path)
    {
        $result = [];
        foreach (explode('/', $path) as $value) {
            if (!empty($value)) {
                $result[] = (int) $value;
            }
        }
        return $result;
    }
}
