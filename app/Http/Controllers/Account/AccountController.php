<?php

namespace App\Http\Controllers\Account;

use App\Helpers\Wormix\WormixBotHelper;
use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Requests\Account\UpdatePhotoRequest;
use App\Http\Resources\Account\UserResource;
use App\Models\User;
use App\Models\UserSocialData;
use App\Modules\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function getAccount(Request $request)
    {
        return $this->getAccountResource($request->user());
    }

    public function getAccountInfo(User $user)
    {
        return $this->getAccountResource($user);
    }

    private function getAccountResource(User $user)
    {
        return new UserResource($user->load([
            'user_profile',
            'char_data',
            'social_data',
            'user_profile.teammates.teammate'
        ]));
    }

    public function updateAccount(UpdateAccountRequest $request)
    {
        if ($request->json('social_data') !== null)
        {
            $socialData = $request->user()->social_data;
            $socialData->fill($request->json('social_data'));
            $socialData->save();
        }

        if ($request->json('user_profile') !== null)
        {
            $userProfile = $request->user()->user_profile;
            $userProfile->fill($request->json('user_profile'));
            $userProfile->save();
        }

        if ($request->json('worm_data') !== null)
        {
            $charData = $request->user()->char_data;

            $charData->fill($request->json('worm_data'));
            $stripParams = WormixBotHelper::stripData(
                (int)(($charData->armor + $charData->attack) / 2),
                $charData->level,
                $charData->armor,
                $charData->attack
            );
            $charData->armor = $stripParams['armor'];
            $charData->attack = $stripParams['attack'];
            $charData->experience = 0;
            $charData->race = $request->json('worm_data.race');
            $charData->save();

            return [
                'worm_data' => $charData,
            ];
        }

        if ($request->json('user') !== null)
        {
            $user = $request->user();
            if ($request->json('user.login') !== null)
            {
                if (User::query()
                        ->where('login', $request->json('user.login'))
                        ->where('id', '!=', $user->id)->count() > 0
                )
                {
                    return response([
                        'message' => 'User login must be unique'
                    ], 422);
                }
                $user->login = $request->json('user.login');
            }

            if ($request->json('user.password') !== null)
            {
                $user->password = $request->json('user.password');
                $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
            }

            if ($user->isDirty([
                'login',
                'password'
            ]))
            {
                $user->save();
            }
        }

        return [
            'success' => true,
        ];
    }

    public function updateAccountPhoto(UpdatePhotoRequest $request)
    {
        $userSocialData = $request->user()->social_data;
        $userPhoto = $request->file('photo');

        $newPhotoName = Str::uuid()->toString().".".$userPhoto->extension();

        if ($userSocialData->photo !== null)
        {
            $oldPhotoPath = resource_path('images/users/'.$userSocialData->photo);
            if (File::exists($oldPhotoPath))
            {
                File::delete($oldPhotoPath);
            }
        }

        $userSocialData->photo = $newPhotoName;
        $userSocialData->save();

        $userPhoto->move(resource_path('images/users/'), $newPhotoName);

        return [
            'data' => $userSocialData
        ];
    }

    /**
     * @throws \Exception
     */
    public function startGame(Request $request)
    {
        $userSession = new UserSession($request->user()->id);
        return response([
            'auth_key' => $userSession->setAuthKey()
        ]);
    }
}
