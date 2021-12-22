<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\ApiMethodGroup.
 *
 * @property string      $name
 * @property string      $description
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 * @property ApiMethod[] $methods
 *
 * @method static ApiMethodGroup whereCreatedAt($value)
 * @method static ApiMethodGroup whereDescription($value)
 * @method static ApiMethodGroup whereName($value)
 * @method static ApiMethodGroup whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin Builder
 *
 * @method static ApiMethodGroup newModelQuery()
 * @method static ApiMethodGroup newQuery()
 * @method static ApiMethodGroup query()
 *
 * @property null|int $methods_count
 */
class ApiMethodGroup extends CachedModel
{
    protected $primaryKey = 'name';
    protected $keyType = 'string';
    protected $table = 'api_method_groups';
    protected $fillable = [
        'name',
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

    public function methods()
    {
        return $this->hasMany(ApiMethod::class, 'group', 'name');
    }
}
