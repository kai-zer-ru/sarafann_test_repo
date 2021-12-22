<?php

namespace App\Services\Api\Versions;

use App\Models\ApiErrorCode;
use App\Models\User;
use App\Services\SaraFannApiService;

/**
 * Models.
 */

/**
 * Class Version_1_0.
 *
 * @property SaraFannApiService $Owner
 */
class Version_1_0
{
    public SaraFannApiService $Owner;

    public function init(SaraFannApiService $Owner)
    {
        $this->Owner = $Owner;

        return $this;
    }

    public function workGetMyData()
    {
        $Owner = $this->Owner;
        $Owner->error = $Owner::ERROR_NO_ERRORS;
        $Owner->errorText = '';
        if (0 === $Owner->CurrentUserID || !$Owner->CurrentUser) {
            $Owner->setError(ApiErrorCode::whereCode(103)->first()->getArray());
            $Owner->errorText = 'Нужна авторизация';

            return [];
        }

        saveLogInfo('currentUser = ');
        saveLogInfo($Owner->CurrentUser);

        return [$Owner->CurrentUser->getFullInfo()];
    }

    public function workGetUserData()
    {
        $Owner = $this->Owner;
        $Owner->error = $Owner::ERROR_NO_ERRORS;
        $OwnerUserIDsString = $Owner->getInputDataString('id', '0');
        if (strstr($OwnerUserIDsString, '.')) {
            $OwnerUserIDs = explode('.', $OwnerUserIDsString);
        } else {
            $OwnerUserIDs = explode(',', $OwnerUserIDsString);
        }
        $OwnerUserIDsArray = [];
        foreach ($OwnerUserIDs as $OwnerUserIDrow) {
            $userId = (int) ($OwnerUserIDrow);
            if ((string) $userId !== (string) $OwnerUserIDrow) {
                continue;
            }
            $OwnerUserIDsArray[] = (int) ($OwnerUserIDrow);
        }
        $OwnerUserIDs = array_unique($OwnerUserIDsArray);
        if (0 === count($OwnerUserIDs)) {
            $Owner->setError(ApiErrorCode::whereCode(102)->first()->getArray());
            $Owner->errorText = 'Неверный id';

            return [];
        }
        $users = User::whereIn('id', $OwnerUserIDs)
            ->get();
        $UserDataAll = [];
        foreach ($users as $user) {
            $UserData = $user->getFullInfo();
            $UserDataAll[] = $UserData;
        }

        if (0 === count($UserDataAll)) {
            return [];
        }

        return $UserDataAll;
    }
}
