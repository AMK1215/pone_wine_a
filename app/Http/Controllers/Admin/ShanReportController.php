<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\ReportTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShanReportController extends Controller
{
    public function index(Request $request)
    {
        $agent = Auth::user();

        $hierarchy = [
            'Owner' => ['Super', 'Senior', 'Master', 'Agent'],
            'Super' => ['Senior', 'Master', 'Agent'],
            'Senior' => ['Master', 'Agent'],
            'Master' => ['Agent'],
        ];

        $query = ReportTransaction::select(
            'report_transactions.user_id',
            'users.name as player_name',
            'agents.name as agent_name',
            DB::raw('COUNT(report_transactions.id) AS transaction_count'),
            DB::raw('SUM(report_transactions.transaction_amount) AS total_transaction_amount'),
            DB::raw('MAX(report_transactions.created_at) AS latest_transaction_date')
        )
            ->join('users', 'report_transactions.user_id', '=', 'users.id')
            ->join('users as agents', 'users.agent_id', '=', 'agents.id')
            ->groupBy('report_transactions.user_id', 'users.name', 'agents.name')
            ->orderByDesc('latest_transaction_date')
            ->when(isset($request->start_date) && isset($request->end_date), function ($query) use ($request) {
                $query->whereBetween('report_transactions.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            });

            if ($agent->hasRole('Senior Owner')) {
                $reportTransactions = $query->get();
            } elseif ($agent->hasRole('Agent')) {
                $agentChildrenIds = $agent->children->pluck('id')->toArray();
                $reportTransactions = $query->whereIn('user_id', $agentChildrenIds)->get();
            } else {
                $agentChildrenIds = $this->getAgentChildrenIds($agent, $hierarchy);
                $reportTransactions = $query->whereIn('user_id', $agentChildrenIds)->get();
            }
        return view('admin.report.shan.index', compact('reportTransactions'));
    }

    public function show($user_id)
    {
        // Query to get all report transactions for a specific user
        $userTransactions = ReportTransaction::where('user_id', $user_id)
            ->orderByDesc('created_at')
            ->get();

        // Get player name
        $player = User::find($user_id);
        $playerName = $player ? $player->user_name : 'Unknown';

        // Calculate Total Bet Amount
        $totalBet = $userTransactions->sum('bet_amount');

        // Calculate Total Win Amount (win_lose_status = 1)
        $totalWin = $userTransactions->where('win_lose_status', 1)->sum('transaction_amount');

        // Calculate Total Lose Amount (win_lose_status = 0)
        $totalLose = $userTransactions->where('win_lose_status', 0)
            ->sum(function ($transaction) {
                return abs($transaction->transaction_amount);
            });

        // Pass the data to the view
        return view('admin.report.shan.show', compact(
            'userTransactions',
            'user_id',
            'totalBet',
            'totalWin',
            'totalLose',
            'playerName'
        ));
    }

    public function ShanAgentReportIndex(Request $request)
    {
        $authUser = auth()->user(); // Get the authenticated agent

        // Fetch report data for users (players) related to the authenticated agent
        $reportTransactions = ReportTransaction::select(
            'report_transactions.user_id',
            'users.name',
            DB::raw('COUNT(report_transactions.id) AS transaction_count'),
            DB::raw('SUM(report_transactions.transaction_amount) AS total_transaction_amount'),
            DB::raw('MAX(report_transactions.created_at) AS latest_transaction_date') // Use MAX or MIN for created_at
        )
            ->join('users', 'report_transactions.user_id', '=', 'users.id')
            ->where('users.agent_id', $authUser->id) // Filter users by the agent's ID
            ->groupBy('report_transactions.user_id', 'users.name')
            ->orderByDesc('latest_transaction_date') // Now ordering by the alias of the aggregate function
            ->when(isset($request->start_date) && isset($request->end_date), function ($query) use ($request) {
                $query->whereBetween('report_transactions.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
            })
            ->get();

        return view('admin.shan.reports.agentindex', compact('reportTransactions'));
    }

    private function getAgentChildrenIds($agent, array $hierarchy)
    {
        foreach ($hierarchy as $role => $levels) {
            if ($agent->hasRole($role)) {
                return collect([$agent])
                    ->flatMap(fn($levelAgent) => $this->getChildrenRecursive($levelAgent, $levels))
                    ->pluck('id')
                    ->toArray();
            }
        }
        return [];
    }

    private function getChildrenRecursive($agent, array $levels)
    {
        $children = collect([$agent]);
        foreach ($levels as $level) {
            $children = $children->flatMap->children;
        }
        return $children->flatMap->children;
    }
}
