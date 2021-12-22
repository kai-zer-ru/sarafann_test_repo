<?php
/**
 * Copyright  (c).
 *
 * @author kaizer
 * @email kaizer@kai-zer.ru
 * @project SaraFann
 * @file User.php
 * @updated 2019-8-5
 */

namespace App\Models;

use App\Services\Api\Funcs;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\Builder;
use morphos\Russian\Cases;
use function morphos\Russian\inflectName;

/**
 * App\Models\User.
 *
 * @property int             $id
 * @property null|string     $email
 * @property null|string     $first_name
 * @property null|string     $last_name
 * @property null|string     $description
 * @property null|string     $phone
 * @property null|int        $code
 * @property null|int        $status
 * @property null|Carbon     $created_at
 * @property null|Carbon     $updated_at
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 *
 * @property string               $web_url
 * @property string               $web_url_full
 */
class User extends CachedModel
{
    /**
     * Константы.
     */
    public const STATUS_PENDING = 0;
    public const STATUS_ACTIVE = 2;
    public const STATUS_BANNED = 3;
    public const STATUS_DELETED_FOREVER = 4;
    public const STATUS_WAIT_FOR_CONFIRM = 6;
    public const STATUS_PARSING = 7;
    public const STATUS_DELETED = 8;

    public const PHONE_STATUS_NEW = 0;
    public const PHONE_STATUS_CONFIRMED = 1;
    public const PHONE_STATUS_IN_PROGRESS = 2;

    public const INTERVAL_ONLINE = 30;

    public const GENDER_WOMAN = 0;
    public const GENDER_MAN = 1;

    public const PERCENT_POSITIVE = 1;
    public const PERCENT_NEUTRAL = 2;
    public const PERCENT_NEGATIVE = 3;
    public const PERCENT_STAR_1 = 4;
    public const PERCENT_STAR_2 = 5;
    public const PERCENT_STAR_3 = 6;
    public const PERCENT_STAR_4 = 7;
    public const PERCENT_STAR_5 = 8;

    public static array $searchableColumns = [
        'full_name' => 50,
    ];
    protected $dates = ['accessed_at'];
    protected $table = 'users';

    protected $fillable = [
        'id',
        'email',
        'first_name',
        'last_name',
        'description',
        'phone',
        'code',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $with = [
    ];

    public static function create($attributes)
    {
        $m = (new self())->setRawAttributes($attributes);
        $m->save(false);

        return $m;
    }

    public function save($needSendNotify = true, array $options = [])
    {
        $saved = parent::save($options);
        $this->onEdit($needSendNotify);

        return $saved;
    }

    public function onEdit(bool $needSendNotify = true)
    {
        Funcs::getCurlJson(env('ELASTIC_DAEMON_URL').'editUser?user_id='.$this->id, null, false);
    }

    /**
     * Permission use email.
     *
     * @param mixed $user
     */
    public static function isAvailableEmail(string $email, $user = false): bool
    {
        $oldUser = self::whereRaw('LOWER(email) = ?', strtolower($email));
        if ($user instanceof User) {
            $oldUser = $oldUser
                ->where('id', '!=', $user->id);
        }
        $oldUser = $oldUser
            ->where('status', '!=', self::STATUS_DELETED_FOREVER)
            ->first();
        if ($oldUser) {
            return false;
        }

        return true;
    }

    /**
     * Permission use phone.
     *
     * @param mixed $user
     */
    public static function isAvailablePhone(string $phone, $user = false): bool
    {
        /**
         * @var User $oldUser
         */
        $oldUser = self::where('phone', '=', $phone)
            ->where('id', '!=', $user->id)
            ->where('status', '!=', self::STATUS_DELETED_FOREVER)
            ->first();
        if ($oldUser) {
            return false;
        }

        return true;
    }

    public function getNamesAttribute(): array
    {
        try {
            $data = [
                'nom' => $this->first_name,
                'gen' => inflectName($this->first_name, Cases::RODIT),
                'dat' => inflectName($this->first_name, Cases::DAT),
                'acc' => inflectName($this->first_name, Cases::VINIT),
                'ins' => inflectName($this->first_name, Cases::TVORIT),
                'abl' => inflectName($this->first_name, Cases::PREDLOJ),
            ];
        } catch (Exception $e) {
            saveLogError($e);
            $data = [
                'nom' => $this->first_name,
                'gen' => $this->first_name,
                'dat' => $this->first_name,
                'acc' => $this->first_name,
                'ins' => $this->first_name,
                'abl' => $this->first_name,
            ];
        }

        return $data;
    }

    /**
     * функция получения стандартной инфы о несуществующем пользователе.
     */
    public static function getDefaultLightInfo()
    {
        $row['id'] = 0;
        $row['description'] = '';
        $row['name'] = '';
        $row['web_url'] = '';
        $row['web_url_full'] = '';

        return $row;
    }

    public function getWebUrlAttribute(): string
    {
        return '/user/'.$this->id;
    }

    public function getWebUrlFullAttribute(): string
    {
        return env('MAIN_DOMAIN', 'https://test.sarafann.com').$this->web_url;
    }

    public function isActive(): bool
    {
        return self::STATUS_ACTIVE === $this->status;
    }
    /**
     * СОздать токен.
     */
    public function generateAuthToken(): array
    {
        global $authToken;
        $authToken = md5(microtime(true));
        $payload = [
            'iss' => $_SERVER['HTTP_HOST'],
            'user_id' => $this->id,
            'token' => $authToken,
        ];
        $token = JWT::encode($payload, env('JWT_KEY'));

        return ['key' => $token];
    }

    /**
     * Получить коммерчески интерес
     */

    public function getFullInfo(): array
    {
        $noUser = true;
        $isMe = false;
        if ($this->id === authid()) {
            $noUser = false;
            $isMe = true;
        } elseif (self::STATUS_ACTIVE === $this->status) {
            $noUser = false;
        }
        if ($noUser) {
            return [
                'id' => $this->id,
                'status' => $this->status,
                'name' => $this->first_name,
                'type' => 'user',
                'web_url' => $this->web_url,
                'web_url_full' => $this->web_url_full,
            ];
        }
        $row = $this->toArray();
        $row['web_url_full'] = $this->web_url_full;
        if ($isMe) {
            $row['phone'] = $this->phone;
            $row['email'] = $this->email;
        }
        $row['first_name'] = $this->first_name;

        if ($isMe) {
            if (iAmAdmin()) {
                $row['is_admin'] = true;
                $row['admin_role'] = getAdminRole();
            }
            if (isModerator()) {
                $row['is_moderator'] = true;
            }
            $row['phone'] = $this->phone;
            $row['first_name'] = $this->first_name;
            $row['email'] = $this->email;
            $row['phone'] = $this->phone;
        } elseif (iAmAdmin()) {
            $row['phone'] = $this->phone;
            $row['first_name'] = $this->first_name;
            $row['email'] = $this->email;
            $row['phone'] = $this->phone;
        } else {
            unset(
                $row['phone'],
            );
        }

        $row['full_info'] = true;
        $row['status'] = $this->status;

        return $row;
    }
}
