<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Product;
use App\Models\PoneWineBet;
use App\Models\PoneWineBetInfo;
use App\Models\PoneWinePlayerBet;
use App\Models\User;
use App\Models\Webhook\BetNResult;
use App\Models\Webhook\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function ponewine()
    {
        $agent = $this->getAgent() ?? Auth::user();
        
        $playerTotals = DB::table('pone_wine_player_bets')
            ->select([
                'user_id',
                'user_name',
                DB::raw('SUM(win_lose_amt) as total_win_lose_amt')
            ])
            ->groupBy('user_id', 'user_name');

        $reports = DB::table('pone_wine_bet_infos')
            ->join('pone_wine_player_bets', 'pone_wine_player_bets.id', '=', 'pone_wine_bet_infos.pone_wine_player_bet_id')
            ->joinSub($playerTotals, 'player_totals', function ($join) {
                $join->on('player_totals.user_id', '=', 'pone_wine_player_bets.user_id');
            })
            ->select([
                'player_totals.user_id',
                'player_totals.user_name',
                'player_totals.total_win_lose_amt',
                DB::raw('SUM(pone_wine_bet_infos.bet_amount) as total_bet_amount')
            ])
            ->groupBy('player_totals.user_id', 'player_totals.user_name', 'player_totals.total_win_lose_amt')
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

    public function index(Request $request)
    {
        $adminId = auth()->id();

        $report = $this->buildQuery($request, $adminId);

        return view('admin.report.index', compact('report'));
    }

    public function getReportDetails(Request $request, $playerId)
    {

        $details = $this->getPlayerDetails($playerId, $request);

        $productTypes = Product::where('is_active', 1)->get();

        return view('admin.report.detail', compact('details', 'productTypes', 'playerId'));
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

    private function buildQuery(Request $request, $adminId)
    {
        $startDate = $request->start_date ??  Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $resultsSubquery = Result::select(
            'results.user_id',
            DB::raw('SUM(results.total_bet_amount) as total_bet_amount'),
            DB::raw('SUM(results.win_amount) as win_amount'),
            DB::raw('SUM(results.net_win) as net_win'),
            DB::raw('COUNT(results.game_code) as total_count'),
        )
            ->groupBy('results.user_id')
            ->whereBetween('results.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $betsSubquery = BetNResult::select(
            'bet_n_results.user_id',
            DB::raw('SUM(bet_n_results.bet_amount) as bet_total_bet_amount'),
            DB::raw('SUM(bet_n_results.win_amount) as bet_total_win_amount'),
            DB::raw('SUM(bet_n_results.net_win) as bet_total_net_amount'),
            DB::raw('COUNT(bet_n_results.game_code) as total_count'),

        )
            ->groupBy('bet_n_results.user_id')
            ->whereBetween('bet_n_results.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $query = DB::table('users as players')
            ->select(
                'players.id as user_id',
                'players.name as player_name',
                'players.user_name as user_name',
                'agents.name as agent_name',
                DB::raw('IFNULL(results.total_bet_amount, 0) + IFNULL(bets.bet_total_bet_amount, 0) as total_bet_amount'),
                DB::raw('IFNULL(results.win_amount, 0) + IFNULL(bets.bet_total_win_amount, 0) as total_win_amount'),
                DB::raw('IFNULL(results.net_win, 0) + IFNULL(bets.bet_total_net_amount, 0) as total_net_win'),
                DB::raw('IFNULL(results.total_count, 0) + IFNULL(bets.total_count, 0) as total_count'),
                DB::raw('MAX(wallets.balance) as balance'),
            )
            ->leftJoin('users as agents', 'players.agent_id', '=', 'agents.id')
            ->leftJoin('wallets', 'wallets.holder_id', '=', 'players.id')
            ->leftJoinSub($resultsSubquery, 'results', 'results.user_id', '=', 'players.id') // Fixed alias
            ->leftJoinSub($betsSubquery, 'bets', 'bets.user_id', '=', 'players.id') // Fixed alias
            ->when($request->player_id, fn($query) => $query->where('players.user_name', $request->player_id))
            ->where(function ($query) {
                $query->whereNotNull('results.user_id')
                    ->orWhereNotNull('bets.user_id');
            });

        $this->applyRoleFilter($query, $adminId);

        return $query->groupBy('players.id', 'players.name', 'players.user_name', 'agents.name')->get();
    }

    private function applyRoleFilter($query, $adminId)
    {
        if (Auth::user()->hasRole('Owner')) {
            $query->where('agents.agent_id', $adminId);
        } elseif (Auth::user()->hasRole('Agent')) {
            $query->where('agents.id', $adminId);
        }
    }

    private function getPlayerDetails($playerId, $request)
    {
        $startDate = $request->start_date ??  Carbon::today()->startOfDay()->toDateString();
        $endDate = $request->end_date ?? Carbon::today()->endOfDay()->toDateString();

        $combinedSubquery = DB::table('results')
            ->select(
                'user_id',
                'total_bet_amount',
                'win_amount',
                'net_win',
                'game_lists.game_name',
                'products.provider_name',
                'results.created_at as date',
                'round_id'
            )
            ->join('game_lists', 'game_lists.game_id', '=', 'results.game_code')
            ->join('products', 'products.id', '=', 'game_lists.product_id')
            ->whereBetween('results.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->when($request->product_id, fn($query) => $query->where('products.id', $request->product_id))
            ->unionAll(
                DB::table('bet_n_results')
                    ->select(
                        'user_id',
                        'bet_amount as total_bet_amount',
                        'win_amount',
                        'net_win',
                        'game_lists.game_name',
                        'products.provider_name',
                        'bet_n_results.created_at as date',
                        'tran_id as round_id'
                    )
                    ->join('game_lists', 'game_lists.game_id', '=', 'bet_n_results.game_code')
                    ->join('products', 'products.id', '=', 'game_lists.product_id')
                    ->whereBetween('bet_n_results.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->when($request->product_id, fn($query) => $query->where('products.id', $request->product_id))
            );

        $query = DB::table('users as players')
            ->joinSub($combinedSubquery, 'combined', 'combined.user_id', '=', 'players.id')
            ->where('players.id', $playerId);

        return $query->orderBy('date', 'desc')->get();
    }
}
