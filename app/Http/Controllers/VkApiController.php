<?php

namespace App\Http\Controllers;

use App\Http\Requests\VkApiRequest;
use App\Http\Resources\Vk\VkProfile;
use App\Models\User;
use App\Models\UserSocialData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class VkApiController extends Controller
{
    public function handleRequest(VkApiRequest $request)
    {
        //Log::debug("VK_API_REQUEST", $request->toArray());
        return match ($request->post('method'))
        {
            'getUserSettings' => $this->getUserSettings(),
            'getAppFriends' => $this->getAppFriends(),
            'getProfiles' => $this->getProfiles($request),
            'getUserBalance' => $this->getUserBalance($request),
            default => "", //Other methods not needed
        };
    }

    private function getUserSettings()
    {
        return [
            'response' => 2371351
        ];
    }

    private function getAppFriends()
    {
        return [
            'response' => User::query()->select('id')->get()->pluck('id')->toArray(),
        ];
    }

    private function getProfiles(Request $request)
    {
        $uidsArray = json_decode($request->post('uids'), true);
        $isBots = false;

        foreach ($uidsArray as $uid)
        {
            if ($uid < 0)
            {
                $isBots = true;
                break;
            }
        }

        if (!$isBots)
        {
            return [
                'response' => VkProfile::collection(UserSocialData::all())
            ];
        }

        $profiles = Collection::empty();
        $names = null;

        if (File::exists(resource_path('game/names.json')))
        {
            $names = json_decode(File::get(resource_path('game/names.json')), true);
        }

        if ($names === null)
        {
            $names = ["NaN Bot"];
        }

        foreach ($uidsArray as $uid)
        {
            $randomName = explode(" ", $names[array_rand($names)]);
            $botProfile = new UserSocialData();
            $botProfile->user_id = $uid;
            $botProfile->first_name = $randomName[1];
            $botProfile->last_name = $randomName[0];
            $botProfile->nickname = "bot";

            if (File::exists(resource_path('images/bots')))
            {
                $fileList = File::files(resource_path('images/bots'));
                if (count($fileList) !== 0)
                {
                    $botProfile->photo = $fileList[array_rand($fileList)]->getFilename();
                }
            }
            $profiles->add($botProfile);
        }

        return [
            'response' => VkProfile::collection($profiles)
        ];
    }

    private function getUserBalance()
    {
        return [
            'response' => config('wormix.vk_balance') * 100
        ];
    }
}