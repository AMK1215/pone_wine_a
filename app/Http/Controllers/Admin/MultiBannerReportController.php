<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Webhook\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MultiBannerReportController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function getSeniorReport()
    {
        $seniorId = auth()->id();

        // Get all Admins under this Senior
        $admins = User::where('agent_id', $seniorId)->get();

        // Aggregate data for each Admin
        $data = [];
        foreach ($admins as $admin) {
            $agents = User::where('agent_id', $admin->id)->get();

            foreach ($agents as $agent) {
                $players = User::where('agent_id', $agent->id)->get();

                $results = Result::whereIn('user_id', $players->pluck('id'))
                    ->selectRaw('SUM(total_bet_amount) as total_bets, SUM(win_amount) as total_wins, SUM(net_win) as total_net')
                    ->first();

                $data[] = [
                    'admin_name' => $admin->name,
                    'agent_name' => $agent->name,
                    'total_bets' => $results->total_bets ?? 0,
                    'total_wins' => $results->total_wins ?? 0,
                    'total_net' => $results->total_net ?? 0,
                ];
            }
        }

        return view('admin.reports.senior.index', compact('data'));
    }

    public function getAdminReport()
    {
        $adminId = auth()->id();

        // Get all Agents under this Admin
        $agents = User::where('agent_id', $adminId)->get();

        // Aggregate data for each Agent
        $data = [];
        foreach ($agents as $agent) {
            $players = User::where('agent_id', $agent->id)->get();

            $results = Result::whereIn('user_id', $players->pluck('id'))
                ->selectRaw('SUM(total_bet_amount) as total_bets, SUM(win_amount) as total_wins, SUM(net_win) as total_net')
                ->first();

            $data[] = [
                'agent_name' => $agent->name,
                'total_bets' => $results->total_bets ?? 0,
                'total_wins' => $results->total_wins ?? 0,
                'total_net' => $results->total_net ?? 0,
            ];
        }

        return view('admin.reports.owner.index', compact('data'));
    }

    public function getAgentReport()
    {
        $agent = $this->getAgent() ?? Auth::user();
        // Get all Players under this Agent
        $players = User::where('agent_id', $agent->id)->get();

        // Aggregate data for Players
        $results = Result::whereIn('user_id', $players->pluck('id'))
            ->selectRaw('user_id, SUM(total_bet_amount) as total_bets, SUM(win_amount) as total_wins, SUM(net_win) as total_net')
            ->groupBy('user_id')
            ->get();

        return view('admin.reports.agent.index', compact('results'));
    }

    // related agent's player report detail
    public function getAgentDetail($userId)
    {
        // Fetch the player details
        $player = User::findOrFail($userId);

        // Fetch all results for the player
        $details = Result::where('user_id', $userId)->get();

        return view('admin.reports.agent.detail', compact('player', 'details'));
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
