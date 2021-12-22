<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * App\Models\ApiMethod.
 *
 * @property int         $id
 * @property string      $group
 * @property string      $name
 * @property string      $description
 * @property string      $response_description
 * @property string      $function_name
 * @property int         $status
 * @property int         $first_version
 * @property int         $last_version
 * @property int         $is_hidden
 * @property int         $is_deprecated
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 *
 * @method static ApiMethod whereCreatedAt($value)
 * @method static ApiMethod whereDescription($value)
 * @method static ApiMethod whereFirstVersion($value)
 * @method static ApiMethod whereFunctionName($value)
 * @method static ApiMethod whereGroup($value)
 * @method static ApiMethod whereId($value)
 * @method static ApiMethod whereLastVersion($value)
 * @method static ApiMethod whereName($value)
 * @method static ApiMethod whereResponseDescription($value)
 * @method static ApiMethod whereStatus($value)
 * @method static ApiMethod whereUpdatedAt($value)
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 *
 * @property ApiAccessTokenType[]|Collection $accessTokens
 * @property ApiErrorCode[]|Collection       $errors
 * @property ApiVersion                      $firstVersion
 * @property ApiVersion                      $lastVersion
 * @property string                          $request_schema_url
 * @property string                          $request_schema_path
 * @property string                          $response_schema_url
 * @property string                          $response_schema_path
 *
 * @method static ApiMethod whereIsHidden($value)
 * @method static ApiMethod whereIsDeprecated($value)
 * @method static ApiMethod newModelQuery()
 * @method static ApiMethod newQuery()
 * @method static ApiMethod query()
 *
 * @property null|int $access_tokens_count
 * @property null|int $errors_count
 */
class ApiMethod extends CachedModel
{
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;
    public const STATUS_TEST = 2;

    protected $table = 'api_methods';
    protected $appends = [
        'request_schema_url',
        'response_schema_url',
    ];
    protected $fillable = [
        'id',
        'group',
        'name',
        'description',
        'response_description',
        'function_name',
        'status',
        'first_version',
        'last_version',
        'is_hidden',
        'is_deprecated',
        'created_at',
        'updated_at',
    ];
    protected $hidden = [
        'pivot',
    ];

    public static function create($attributes)
    {
        $m = (new self())->setRawAttributes($attributes);
        $m->save();

        return $m;
    }

    public function errors()
    {
        return $this->belongsToMany(ApiErrorCode::class, 'api_method_errors', 'method_id', 'error_id');
    }

    public function accessTokens()
    {
        return $this->belongsToMany(ApiAccessTokenType::class, 'api_method_tokens', 'method_id', 'token_id');
    }

    public function getRequestSchemaUrlAttribute()
    {
        return '/json/schema/methods/'.$this->group.'/'.$this->name.'.request.json'.'?i='.rand(1, 1000);
    }

    public function getResponseSchemaUrlAttribute()
    {
        return '/json/schema/methods/'.$this->group.'/'.$this->name.'.response.json'.'?i='.rand(1, 1000);
    }

    public function getRequestSchemaPathAttribute()
    {
        return public_path('/json/schema/methods/'.$this->group.'/'.$this->name.'.request.json');
    }

    public function getResponseSchemaPathAttribute()
    {
        return public_path('/json/schema/methods/'.$this->group.'/'.$this->name.'.response.json');
    }

    public function firstVersion()
    {
        return $this->hasOne(ApiVersion::class, 'id', 'first_version');
    }

    public function lastVersion()
    {
        return $this->hasOne(ApiVersion::class, 'id', 'last_version');
    }
}
