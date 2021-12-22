<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * App\Models\ApiAccessTokenType.
 *
 * @property int         $id
 * @property null|string $name
 * @property null|string $description
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 *
 * @method static ApiAccessTokenType whereCreatedAt($value)
 * @method static ApiAccessTokenType whereDescription($value)
 * @method static ApiAccessTokenType whereId($value)
 * @method static ApiAccessTokenType whereName($value)
 * @method static ApiAccessTokenType whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin Builder
 *
 * @property string $type
 * @property mixed  $token_name
 *
 * @method static ApiAccessTokenType whereType($value)
 * @method static ApiAccessTokenType newModelQuery()
 * @method static ApiAccessTokenType newQuery()
 * @method static ApiAccessTokenType query()
 */
class ApiAccessTokenType extends CachedModel
{
    public const TYPE_APPLICATION = 'app';
    public const TYPE_USER = 'user';

    protected $table = 'api_access_token_types';
    protected $fillable = [
        'id',
        'type',
        'name',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $appends = [
        'token_name',
    ];
    protected $hidden = [
        'pivot',
    ];

    public function getTokenNameAttribute()
    {
        if ('app' === $this->type) {
            return 'AccessTokenApp';
        }

        return 'AccessTokenUser';
    }

    public static function create($attributes)
    {
        $m = (new self())->setRawAttributes($attributes);
        $m->save();

        return $m;
    }
}
