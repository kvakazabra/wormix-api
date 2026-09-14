<?php

namespace App\Http\Resources\Internal\Account;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Resources\Internal\House\BackpackConfStructure;
use App\Models\Wormix\LoginSequence;
use App\Models\Wormix\UserBackpack;
use App\Models\Wormix\UserBattleInfo;
use App\Models\Wormix\UserCookies;
use App\Models\Wormix\UserProfile;
use App\Http\Resources\Internal\Arena\ReconnectToSimpleBattleResultStructure;
use App\Models\Wormix\CharData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read UserBattleInfo $battle_info
 * @property-read UserProfile $user_profile
 * @property-read UserBackpack $backpack
 * @property-read CharData $char_data
 * @property-read LoginSequence $login_sequence
 * @property-read UserCookies $cookies
 */
class EnterAccount extends JsonResource
{
    private string $session_key;

    public function __construct($resource, $sessionKey)
    {
        $this->session_key = $sessionKey;
        parent::__construct($resource);
    }

    public function toArray(Request $request) : array
    {
        return [
            'UserProfileStructure' =>
                new UserProfileStructure($this->user_profile),
            'UserProfileStructures' =>
                [], //UserProfileStructure::collection(UserProfile::query()->where('user_id', '!=', $this->id)->get()),
            'LoginAwards' =>
                [], // todo
            'OnlineFriends' => 0,
            'SessionKey' => $this->session_key,
            'Friends' => 0, // UserProfile::query()->where('user_id', '!=', $this->id)->count(),
            'AvailableSearchKeys' =>
                WormixTrashHelper::getSearchKeys($this->id),
            'Reagents' =>
                $this->user_profile->reagents,
            'CurSoloMissionId' =>
                $this->battle_info->solo_mission_id,
            'CurCooperativeMissionId' =>
                $this->battle_info->coop_mission_id,
            'Invites' => [],
            'ServerTime' => time(),
            'BackpackConfs' =>
                $this->backpack->configurations,
            'ActiveBackpackConf' =>
                $this->backpack->current_configuration,
            'Hotkeys' =>
                $this->backpack->hotkeys,
            'LoginSequence' =>
                $this->login_sequence->login_sequence,
            'Races' =>
                WormixTrashHelper::generateRacesBitfield($this->char_data->races),
            'SelectRaceTimeLeft' =>
                $this->user_profile->race_change_timestamp,
            'Skins' =>
                $this->char_data->skins,
            'LastPaymentTime' => time(),
            'Restrictions' => [],
            'Cookies' =>
                (object)$this->cookies?->cookies,
            'VipSubscriptionId' => 0,
            'HasReconnectResult' => false,
            'ReconnectResult' =>
                new ReconnectToSimpleBattleResultStructure($this),
        ];
    }
}
