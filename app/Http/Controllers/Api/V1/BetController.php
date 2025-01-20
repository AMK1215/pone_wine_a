<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\User;
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
            DB::beginTransaction();

            $results = [[
                'player_id' => $banker->user_name,
                'balance' => $banker->wallet->balance
            ]];

            foreach ($validatedData['players'] as $playerData) {
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

    private function getUserByUsername(string $username): ?User
    {
        return User::where('user_name', $username)->first();
    }

    private function handleBankerTransaction(User $banker, array $bankerData, int $gameTypeId): void
    {
        Bet::create([
            'user_id' => $banker->id,
            'game_type_id' => $gameTypeId,
            'transaction_amount' => $bankerData['amount'],
            'final_turn' => $bankerData['is_final_turn'] ? 1 : 0,
            'banker' => 1,
        ]);

        if ($bankerData['is_final_turn']) {
            $banker->wallet->balance += $bankerData['amount'];
            $banker->wallet->save();
        }
    }

    private function handlePlayerTransaction(User $player, array $playerData, int $gameTypeId): void
    {
        Bet::create([
            'user_id' => $player->id,
            'game_type_id' => $gameTypeId,
            'transaction_amount' => $playerData['amount_changed'],
            'status' => $playerData['win_lose_status'],
            'bet_amount' => $playerData['bet_amount'],
            'valid_amount' => $playerData['bet_amount'],
        ]);

        $this->updatePlayerBalance($player, $playerData['amount_changed'], $playerData['win_lose_status']);
    }

    private function updatePlayerBalance(User $player, float $amountChanged, int $winLoseStatus): void
    {
        if ($winLoseStatus === 1) {
            $player->wallet->balance += $amountChanged;
        } else {
            $player->wallet->balance -= $amountChanged;
        }
        $player->wallet->save();
    }
}
