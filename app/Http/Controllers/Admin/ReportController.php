<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoneWineBet;
use App\Models\PoneWineBetInfo;
use App\Models\PoneWinePlayerBet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function ponewine()
    {
        $agent = $this->getAgent() ?? Auth::user();
        $reports = DB::table('pone_wine_bets')
            ->join('pone_wine_player_bets', 'pone_wine_player_bets.pone_wine_bet_id', '=', 'pone_wine_bets.id')
            ->join('pone_wine_bet_infos', 'pone_wine_bet_infos.pone_wine_player_bet_id', '=', 'pone_wine_player_bets.id') // Fixed join
            ->select([
                DB::raw('SUM(pone_wine_player_bets.win_lose_amt) as total_win_lose_amt'),
                DB::raw('SUM(pone_wine_bet_infos.bet_amount) as total_bet_amount'),
                'pone_wine_player_bets.user_name',
                'pone_wine_player_bets.user_id',
            ])
            ->groupBy([
                'pone_wine_player_bets.user_id',
                'pone_wine_player_bets.user_name',
            ])
            ->get();

        return view('admin.report.ponewine.index', compact('reports'));
    }


    public function detail($playerId)
    {
        $reports = DB::table('pone_wine_bet_infos')
            ->join('pone_wine_player_bets', 'pone_wine_player_bets.id', '=', 'pone_wine_bet_infos.pone_wine_player_bet_id')
            ->join('pone_wine_bets', 'pone_wine_bets.id', '=', 'pone_wine_player_bets.pone_wine_bet_id')
            ->select([
                'pone_wine_player_bets.user_name',
                'pone_wine_bet_infos.bet_no',
                'pone_wine_bet_infos.bet_amount',
                'pone_wine_bets.win_number',
                'pone_wine_bets.match_id'
            ])
            ->where('pone_wine_player_bets.user_id', $playerId)
            ->get();
      
        return view('admin.report.ponewine.detail', compact('reports'));
    }

    private function isExistingAgent($userId)
    {
        $user = User::find($userId);

        return $user && $user->hasRole(self::SUB_AGENT_ROlE) ? $user->parent : null;
    }

    private function getAgent()
    {
        return $this->isExistingAgent(Auth::id());
    }
}
