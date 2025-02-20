<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoneWineBet;
use App\Models\PoneWineBetInfo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function ponewine()
    {
        $agent = $this->getAgent() ?? Auth::user();
        $reports = PoneWineBetInfo::with(['poneWinePlayerBet', 'poneWinePlayerBet.poneWineBet'])->get();

        return view('admin.report.ponewine.index', compact('reports'));
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
