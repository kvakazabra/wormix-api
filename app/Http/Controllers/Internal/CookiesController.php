<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Account\SetCookiesRequest;
use App\Models\Wormix\UserCookies;

class CookiesController extends Controller
{
    public function setCookies(SetCookiesRequest $request)
    {
        try
        {
            $userCookies = UserCookies::query()
                ->where('user_id', $request->json('internal_user_id'))
                ->first();
            if ($userCookies === null)
            {
                $userCookies = new UserCookies();
                $userCookies->user_id = $request->json('internal_user_id');
            }

            /* Cookies are stored as a dictionary
             * And they arrive here in two separate arrays:
             * - 'Names' containing keys
             * - 'Values' containing values ofc
             */
            $names = $request->json('Names');
            $values = $request->json('Values');
            if (count($names) != count($values)) {
                return [
                    'Response' => 'Bad data'
                ];
            }

            $cookies = $userCookies->cookies;
            for ($i = 0; $i < count($names); $i++)
            {
                $key = $names[$i];
                $value = $values[$i];
                // Value can be set to null
                // But the core project can not accept those
                // So we just clear an entry in a dictionary if that's the case
                if ($value === null)
                {
                    unset($cookies[$key]);
                }
                else
                {
                    $cookies[$key] = $value;
                }
            }

            $userCookies->cookies = $cookies;
            $userCookies->save();

            return [
                'Response' => 'OK',
            ];
        }
        catch (\Exception $e)
        {
            return [
                'Response' => 'Error',
            ];
        }
    }
}
