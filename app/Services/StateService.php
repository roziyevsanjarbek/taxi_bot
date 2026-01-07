<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StateService
{
    public function setState($chatId, $state)
    {
        DB::table('user_states')->updateOrInsert(
            ['chat_id' => $chatId],
            ['state' => $state]
        );
    }

    public function getState($chatId)
    {
        return DB::table('user_states')->where('chat_id', $chatId)->value('state');
    }
}
