<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\Account\SetBackpackConfRequest;
use App\Http\Requests\Internal\Account\SetHotkeysRequest;
use App\Http\Resources\Internal\Account\SetBackpackConfResult;
use App\Models\User;

class BackpackController extends Controller
{
    public function setHotkeys(SetHotkeysRequest $request)
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->first();

        $backpack = $user->backpack;
        $backpack->hotkeys = $request->json('Hotkeys');
        $backpack->save();

        return [
            'Response' => 'OK',
        ];
    }

    public function setBackpackConf(SetBackpackConfRequest $request)
    {
        $user = User::query()
            ->where('id', $request->json('internal_user_id'))
            ->first();

        $backpack = $user->backpack;
        $backpack->current_configuration = $request->json('ActiveConfig');
        $backpack->configurations = $request->json('Configs');
        $backpack->save();

        return [
            'data' => new SetBackpackConfResult($backpack, 0),
        ];
    }
}
