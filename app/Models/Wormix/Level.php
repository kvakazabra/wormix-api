<?php

namespace App\Models\Wormix;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property int id
 * @property int required_experience
 * @property int team_size
 * @property array awards
 */
class Level extends Model
{
    protected $table = 'wormix_levels';

    protected $fillable = [
        'id',
        'required_experience',
        'team_size',
        'awards'
    ];

    protected $casts = [
        'awards' => 'json'
    ];

    public function award(User $user) : void
    {
        $profile = $user->profile;
        $arena = $user->arena;

        $awards = $this->awards;
        $profile->money += $awards['money'] ?? 0;
        $profile->real_money += $awards['real'] ?? 0;
        $profile->save();

        // Ignore exceptions here
        $profile->grantReagents(
            WormixTrashHelper::pairsToAssociativeArray($awards['reagents'] ?? []),
            true
        );
        $profile->grantItems(
            WormixTrashHelper::pairsToAssociativeArray($awards['weapons'] ?? []),
            true,
            true
        );

        $arena->battle_tokens += $awards['battleTokens'] ?? 0;
        $arena->wager_tokens += $awards['wagerTokens'] ?? 0;
        $arena->boss_tokens += $awards['bossTokens'] ?? 0;
        $arena->save();

        // todo add merc
    }
}
