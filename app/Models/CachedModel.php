<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as MainModel;
use Predis\Client;

/**
 * App\Models\CachedModel.
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static CachedModel newModelQuery()
 * @method static CachedModel newQuery()
 * @method static CachedModel query()
 */
class CachedModel extends MainModel
{
    public $redis;
    protected $connection = 'default';

    public function __construct()
    {
        /**
         * @var Client $redis
         */
        parent::__construct();
        global $redis;
        $this->redis = $redis;
    }

    public function setRawAttributes($attributes, $sync = false)
    {
        $attributes = self::fillableFromArray($attributes);
        if (!array_key_exists('created_at', $attributes) || strstr($attributes['created_at'], 'T')) {
            $attributes['created_at'] = Carbon::createFromTimestamp(time());
        }
        if (!array_key_exists('updated_at', $attributes) || strstr($attributes['updated_at'], 'T')) {
            $attributes['updated_at'] = Carbon::createFromTimestamp(time());
        }

        return parent::setRawAttributes($attributes, $sync);
    }
}
