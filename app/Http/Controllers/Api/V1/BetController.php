<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BetController extends Controller
{
    use HttpResponses;

    public function index(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'user_id' => 'required',
            'user_name' => 'required',
            'room_id' => 'required',
            'bet_no' => 'required',
            'bet_amount' => 'required',
            'win_lose' => 'required',
            'net_win' => 'required',
            'is_winner' => 'required',
            'status' => 'required',
        ]);

        try {
            $bankerData = $validatedData['banker'];
            $playersData = $validatedData['players'];

            DB::beginTransaction();

            $banker = $this->getUserByUsername($bankerData['player_id']);
            if (! $banker) {
                return $this->error('', 'Banker not found', 404);
            }

            $this->handleBankerTransaction($banker, $bankerData, $validatedData['game_type_id']);
            $results = [['player_id' => $banker->user_name, 'balance' => $banker->wallet->balance]];

            // Handle player transactions
            foreach ($playersData as $playerData) {
                $player = $this->getUserByUsername($playerData['player_id']);
                if ($player) {
                    $this->handlePlayerTransaction($player, $playerData, $validatedData['game_type_id']);
                    $results[] = ['player_id' => $player->user_name, 'balance' => $player->wallet->balance];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Transaction failed', $e->getMessage(), 500);
        }

        return $this->success($results, 'Transaction Successful');
    }


}
