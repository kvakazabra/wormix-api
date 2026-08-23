<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Account\AchieveLoginRequest;
use App\Http\Resources\Internal\Account\AchieveLoginError;
use App\Http\Resources\Internal\Account\AchieveLoginSuccess;
use App\Models\User;
use App\Modules\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class InternalAchieveLoginController extends Controller
{
    public function login(AchieveLoginRequest $request)
    {
        try
        {
            $userId = (int)$request->get('Id');

            $session = new UserSession($userId);
            if($session->getAuthKey() != $request->json('AuthKey') || !$session->isLoggedIn()) {
                return [
                    'type' => 'AchieveLoginError',
                    'data' => new AchieveLoginError($request, 1)
                ];
            }

            return [
                'type' => 'AchieveLoginSuccess',
                'data' => new  AchieveLoginSuccess($request, $userId.'.'.$session->getSessionKey())
            ];
        }
        catch (\Exception $ex)
        {
            Log::error("Internal achieve login error", [
                'request' => $request->json()->all(),
                'message' => $ex->getMessage()
            ]);
            return [
                'type' => 'AchieveLoginError',
                'data' => new AchieveLoginError($request, 500)
            ];
        }
    }
}
