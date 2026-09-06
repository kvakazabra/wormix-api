<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class FileController extends Controller
{
    public function getPhoto($photo, $name)
    {
//        Log::debug("Request photo", [
//            'storage' => $photo,
//            'name' => $name
//        ]);

        $fullPath = "";
        switch (strtolower($photo))
        {
            case "bot":
                $fullPath = resource_path("images/bots/{$name}");
                break;
            case "users":
                $fullPath = resource_path("images/users/{$name}");
                break;
            default:
                $fullPath = resource_path("images/default.png");
        }

        if (!File::exists($fullPath))
        {
            $fullPath = resource_path("images/default.png");
        }

        $file = File::get($fullPath);
        $type = File::mimeType($fullPath);
        $rsp = Response::make($file);
        $rsp->header('Cache-Control', 'no-transform,public,max-age=120');
        $rsp->header('Content-Type', $type);
        return $rsp;
    }
}
