<?php

namespace App\Http\Controllers\Internal;

use App\Helpers\Wormix\WormixTrashHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Account\DistributePointsRequest;
use App\Http\Requests\Internal\Account\SelectStuffRequest;
use App\Http\Resources\Internal\Account\DistributePointsResult;
use App\Http\Resources\Internal\Account\SelectStuffResult;
use App\Models\Wormix\UserItem;
use App\Models\Wormix\WormData;
use Illuminate\Database\Eloquent\Collection;

class InternalAccountController extends Controller
{
    public function selectStuff(SelectStuffRequest $request)
    {
        $userItem = UserItem::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->where('item_id', $request->json('StuffId'))
            ->first();

        if ($userItem === null)
        {
            return [
                'data' => new SelectStuffResult(
                    Collection::empty(),
                    SelectStuffResult::Error,
                    0)
            ];
        }

        $worm = WormData::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->first();

        $worm->hat = $userItem->item_id;
        $worm->save();

        return [
            'data' => new SelectStuffResult(
                Collection::empty(),
                SelectStuffResult::Success,
                $request->json('StuffId')
            )
        ];
    }

    public function distributePoints(DistributePointsRequest $request)
    {
        $wormData = WormData::query()
            ->where('owner_id', $request->json('internal_user_id'))
            ->first();

        $availablePoints = $wormData->level * 2 - ($wormData->armor + $wormData->attack);
        if ($availablePoints < $request->json('Armor') + $request->json('Attack'))
        {
            return [
                'data' => new DistributePointsResult(Collection::empty(), DistributePointsResult::NotEnoughPoints)
            ];
        }

        $wormData->armor += $request->json('Armor');
        $wormData->attack += $request->json('Attack');
        $wormData->save();

        return [
            'data' => new DistributePointsResult(Collection::empty(), DistributePointsResult::Success)
        ];
    }
}