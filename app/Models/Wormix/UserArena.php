<?php

namespace App\Models\Wormix;

use App\Models\User;
use App\Observers\Wormix\UserArenaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int user_id
 * @property int battle_tokens
 * @property int wager_tokens
 * @property int boss_tokens
 * @property int heroic_tokens
 * @property int solo_mission_id
 * @property int coop_mission_id
 * @property int current_battle_id
 *
 * @property int first_used_token_time
 *
 * @property UserBattle current_battle
 */
#[ObservedBy(UserArenaObserver::class)]
class UserArena extends Model
{
    protected $table = 'wormix_users_arenas';

    protected $primaryKey = 'user_id';

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    public function current_battle() : BelongsTo
    {
        return $this->belongsTo(UserBattle::class, 'id', 'current_battle_id');
    }

    public function checkTimeGrantBattles() : void
    {
        if ($this->first_used_token_time === 0)
        {
            return;
        }

        $battleDelaySeconds = config('wormix.game.missions.delay');
        $currentTime = time();
        $passedTime = $currentTime - $this->first_used_token_time;
        if ($passedTime < 0)
        {
            return;
        }

        $restoredTokens = intdiv($passedTime, $battleDelaySeconds);
        if ($restoredTokens === 0)
        {
            return;
        }

        $this->first_used_token_time += $restoredTokens * $battleDelaySeconds;

        $maxTokens = config('wormix.game.missions.max');
        $maxToRestore = max($maxTokens - $this->battle_tokens, 0);
        $this->battle_tokens += min($maxToRestore, $restoredTokens);
        $this->save();
    }

    /**
     * @return int Delay in seconds
     */
    public function nextBattleTokenDelay() : int
    {
        $this->checkTimeGrantBattles();

        if($this->first_used_token_time === 0)
        {
            return 0;
        }

        $passedTime = time() - $this->first_used_token_time;
        if($passedTime < 0)
        {
            return 0;
        }

        $battleDelaySeconds = config('wormix.game.missions.delay');
        if($passedTime >= $battleDelaySeconds)
        {
            return $battleDelaySeconds;
        }

        return $battleDelaySeconds - $passedTime;
    }
}
