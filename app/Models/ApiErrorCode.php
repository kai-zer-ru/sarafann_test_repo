<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\ApiErrorCode.
 *
 * @property int         $id
 * @property int         $code
 * @property string      $title
 * @property string      $description
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 *
 * @method static ApiErrorCode whereCreatedAt($value)
 * @method static ApiErrorCode whereDescription($value)
 * @method static ApiErrorCode whereId($value)
 * @method static ApiErrorCode whereTitle($value)
 * @method static ApiErrorCode whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin Builder
 *
 * @property ApiMethod[]|Collection $methods
 *
 * @method static ApiErrorCode whereCode($value)
 * @method static ApiErrorCode newModelQuery()
 * @method static ApiErrorCode newQuery()
 * @method static ApiErrorCode query()
 *
 * @property null|int $methods_count
 */
class ApiErrorCode extends CachedModel
{
    protected $table = 'api_error_codes';
    protected $fillable = [
        'id',
        'code',
        'title',
        'description',
        'created_at',
        'updated_at',
    ];

    public static function create($attributes)
    {
        $m = (new self())->setRawAttributes($attributes);
        $m->save();

        return $m;
    }

    public function getArray()
    {
        return [
            'error' => $this->code,
            'error_text' => $this->title,
            'error_url' => '', //env("API_URL_PROTOCOL") . "://" . env("MAIN_URL", "sarafann.com") . "/dev/errors/" . $this->code,
        ];
    }

    public function methods()
    {
        return $this->belongsToMany(ApiMethod::class, 'api_method_errors', 'error_id', 'method_id');
    }
}
