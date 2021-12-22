<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\ApiVersion.
 *
 * @property int         $id
 * @property float       $version_id
 * @property string      $description
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 *
 * @method static ApiVersion whereCreatedAt($value)
 * @method static ApiVersion whereDescription($value)
 * @method static ApiVersion whereId($value)
 * @method static ApiVersion whereUpdatedAt($value)
 * @method static ApiVersion whereVersionId($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin Builder
 *
 * @method static ApiVersion newModelQuery()
 * @method static ApiVersion newQuery()
 * @method static ApiVersion query()
 */
class ApiVersion extends CachedModel
{
    protected $table = 'api_versions';
    protected $fillable = [
        'id',
        'version_id',
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
}
