<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Telegram\TelegramHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    //
    public function webhook(Request $request, TelegramHandler $handler)
    {
        $update = $request->all();

        // TEXT message
        if (isset($update['message'])) {
            $handler->handleMessage($update['message']);
        }

        // BUTTON bosilganda
        if (isset($update['callback_query'])) {
            $handler->handleCallback($update['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

}
