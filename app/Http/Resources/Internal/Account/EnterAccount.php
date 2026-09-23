<?php

namespace App\Http\Resources\Internal\Account;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\User;
use App\Http\Resources\Internal\Arena\ReconnectToSimpleBattleResultStructure;
use App\Models\Wormix\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User $resource
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
                new UserProfileStructure($this->resource->profile),
            'UserProfileStructures' =>
                UserProfileStructure::collection(UserProfile::query()
                    ->where('user_id', '!=', $this->resource->id)
                    ->get()
                ),
            'LoginAwards' =>
                [], // todo
            'OnlineFriends' => 0,
            'SessionKey' => $this->session_key,
            'Friends' => UserProfile::query()
                ->where('user_id', '!=', $this->resource->id)
                ->count(),
            'AvailableSearchKeys' =>
                WormixTrashHelper::getSearchKeys($this->resource->id),
            'Reagents' =>
                WormixTrashHelper::toIndexedReagentsArray($this->resource->profile->reagents),
            'CurSoloMissionId' =>
                $this->resource->arena->solo_mission_id,
            'CurCooperativeMissionId' =>
                $this->resource->arena->coop_mission_id,
            'Invites' => [],
            'ServerTime' => time(),
            'BackpackConfs' =>
                $this->resource->backpack->configurations,
            'ActiveBackpackConf' =>
                $this->resource->backpack->current_configuration,
            'Hotkeys' =>
                $this->resource->backpack->hotkeys,
            'LoginSequence' =>
                $this->resource->login_sequence->login_sequence,
            'Races' =>
                WormixTrashHelper::generateRacesBitfield($this->resource->profile->races),
            'SelectRaceTimeLeft' =>
                $this->resource->profile->race_change_timestamp,
            'Skins' =>
                $this->resource->profile->skins,
            'LastPaymentTime' => time(),
            'Restrictions' => [],
            'Cookies' =>
                (object)($this->resource->cookies?->cookies ?? []),
            'VipSubscriptionId' => 0,
            'HasReconnectResult' => false,
            'ReconnectResult' =>
                new ReconnectToSimpleBattleResultStructure($this),
        ];
    }
}
