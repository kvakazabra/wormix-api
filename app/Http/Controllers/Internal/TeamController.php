<?php

namespace App\Http\Controllers\Internal;

use App\Exceptions\Wormix\InternalServerException;
use App\Exceptions\Wormix\NotEnoughMoneyException;
use App\Exceptions\Wormix\SecurityException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Team\AddToGroupRequest;
use App\Http\Requests\Internal\Team\RemoveFromGroupRequest;
use App\Http\Requests\Internal\Team\ReorderGroupRequest;
use App\Http\Requests\Internal\Team\SelectStuffRequest;
use App\Http\Requests\Internal\Team\ToggleTeamMemberRequest;
use App\Http\Resources\Internal\Team\SelectStuffResult;
use App\Http\Resources\Internal\Team\TeamResult;
use App\Models\User;
use App\Models\Wormix\CharData;
use App\Models\Wormix\Mercenary;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function add(AddToGroupRequest $request)
    {
        $profileId = $request->json('ProfileId');
        $useRealMoney = $request->json('MoneyType') === 0;
        $teamMemberType = $request->json('TeamMemberType');
        $replaceableId = $request->json('ReplaceableId');
        $isActive = $request->json('Active');

        try
        {
            DB::beginTransaction();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profile = $user->profile;
            $char = $user->char();
            $level = $char->level_model;

            $maxTeamSize = $level->team_size;
            $maxRosterSize = $maxTeamSize + $profile->extra_group_slots;

            if ($replaceableId)
            {
                $teammate = $profile->teammateByProfileId($replaceableId);
                if (!$teammate)
                {
                    throw new InternalServerException('Teammate not found');
                }

                $teammate->char->delete();
            }

            if ($isActive && count($profile->activeTeammates) >= $maxTeamSize)
            {
                throw new InternalServerException('IsActive=true but team is already full');
            }

            // Check if overall teammates size exceed the total count of chars that user can hire
            if (count($profile->teammates) >= $maxRosterSize)
            {
                throw new SecurityException('Exceeding roster size with a new hire up');
            }

            // Predefined mercenaries from MercConfig
            if ($profileId < 0)
            {
                $mercenary = Mercenary::query()
                    ->where('id', $profileId)
                    ->firstOrFail();

                $mercenary->assignTo($user, $isActive, $useRealMoney);
            }
            // Friend or a clan-mate
            else
            {
                $friend = User::query()
                    ->where('id', $profileId)
                    ->firstOrFail();

                $friend->char()->assignTo($user, $isActive, $useRealMoney);
            }

            DB::commit();

            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Success)
            ];
        }
        catch(NotEnoughMoneyException)
        {
            DB::rollBack();
            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::NotEnoughMoney)
            ];
        }
        catch(Exception $e)
        {
            DB::rollBack();
            Log::error($e);
            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Error)
            ];
        }
    }

    public function toggle(ToggleTeamMemberRequest $request)
    {
        try
        {
            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profileId = $request->json('TeamMemberId');
            $isActive = $request->json('Active');

            $teammate = $user->profile->teammateByProfileId($profileId);
            if ($teammate)
            {
                $teammate->active = $isActive;
                $teammate->save();
                return [
                    'Response' => 'OK'
                ];
            }

            return [
                'Response' => 'Not Found'
            ];
        }
        catch(Exception $e)
        {
            Log::error($e);
            return [
                'Response' => 'Bad'
            ];
        }
    }

    public function reorder(ReorderGroupRequest $request)
    {
        $profileIds = $request->json('ReorderedWormGroup');

        try
        {
            DB::beginTransaction();

            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profile = $user->profile;

            foreach ($profileIds as $index => $profileId)
            {
                $teammate = $profile->teammateByProfileId($profileId);
                if (!$teammate)
                {
                    throw new InternalServerException('Teammate not found');
                }

                $teammate->order = $index;
                $teammate->save();
            }

            DB::commit();

            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Success)
            ];
        }
        catch(Exception $e)
        {
            Log::error($e);
            DB::rollBack();
            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Error)
            ];
        }
    }

    public function remove(RemoveFromGroupRequest $request)
    {
        $profileId = $request->json('ProfileId');

        try
        {
            $user = User::query()
                ->where('id', $request->json('internal_user_id'))
                ->firstOrFail();

            $profile = $user->profile;

            $teammate = $profile->teammateByProfileId($profileId);
            $teammate?->char->delete();

            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Success)
            ];
        }
        catch(Exception $e)
        {
            Log::error($e);
            return [
                'data' => new TeamResult(Collection::empty(),
                    TeamResult::Error)
            ];
        }
    }

    public function selectStuff(SelectStuffRequest $request)
    {
        // todo: remove profileIds here, use array_keys or smth like this
        $selectStuffs = collect($request->json('SelectStuffs'))->keyBy('ProfileId')->all();
        $profileIds = collect($request->json('SelectStuffs'))->pluck('ProfileId')->toArray();

        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->firstOrFail();

        $profile = $user->profile;

        try
        {
            $equipments = $profile->equipments;

            foreach ($profileIds as $profileId)
            {
                $teammate = $user->profile->teammateByProfileId($profileId);
                if (!$teammate)
                {
                    throw new InternalServerException('Teammate not found');
                }

                $hatId = $selectStuffs[$profileId]['HatId'];
                $artifactId = $selectStuffs[$profileId]['ArtifactId'];

                if ($hatId !== 0 && !$equipments->contains('item_id', $hatId))
                {
                    if ($teammate->char->type !== CharData::TEAM_MEMBER_MERCENARY ||
                        $teammate->char->mercenary()->hat !== $hatId)
                    {
                        throw new SecurityException('Tried to set hat id that user does not own');
                    }
                }

                if ($artifactId !== 0 && !$equipments->contains('item_id', $artifactId))
                {
                    if ($teammate->char->type !== CharData::TEAM_MEMBER_MERCENARY ||
                        $teammate->char->mercenary()->artifact !== $artifactId)
                    {
                        throw new SecurityException('Tried to set artifact id that user does not own');
                    }
                }

                // todo: also check if hat/art is already equipped

                $teammate->char->hat = $hatId;
                $teammate->char->artifact = $artifactId;
                $teammate->char->save();
            }

            return [
                'data' => new SelectStuffResult($profile,
                    SelectStuffResult::Success, $profileIds)
            ];
        }
        catch(Exception $e)
        {
            Log::error($e);
            return [
                'data' => new SelectStuffResult($profile,
                    SelectStuffResult::Error, $profileIds)
            ];
        }
    }
}
