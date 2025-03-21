<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WithDrawRequest;
use App\Services\WalletService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithDrawRequestController extends Controller
{
    protected const SUB_AGENT_ROLE = 'Sub Agent';

    public function index(Request $request)
    {
        $agent = $this->getAgent() ?? Auth::user();

        $start_date = $request->start_date ?? Carbon::today()->startOfDay()->toDateString();
        $end_date   = $request->end_date   ?? Carbon::today()->endOfDay()->toDateString();

        $withdraws = WithDrawRequest::where('agent_id', $agent->id)
            ->when($request->filled('status') && $request->input('status') !== 'all', function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->whereBetween('created_at', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $withdraws->orderBy('id', 'asc');
        } else {
            $withdraws->orderBy('id', 'desc');
        }

        $withdraws = $withdraws->get();

        $totalWithdraws = $withdraws->where('status',1)->sum('amount');
        $totalWithdrawsCount = count($withdraws);

        $totalWithdrawsAndCounts = [
            'totalWithdraws' => $totalWithdraws,
            'totalWithdrawsCount' => $totalWithdrawsCount
        ];

        return view('admin.withdraw_request.index', compact('withdraws', 'totalWithdrawsAndCounts'));
    }

    public function statusChangeIndex(Request $request, WithDrawRequest $withdraw)
    {
        $agent = $this->getAgent() ?? Auth::user();

        $player = User::find($request->player);

        if ($request->status == 1 && $player->balanceFloat < $request->amount) {
            return redirect()->back()->with('error', 'Insufficient Balance!');
        }

        $withdraw->update([
            'status' => $request->status,
        ]);

        if ($request->status == 1) {
            app(WalletService::class)->transfer(
                $player,
                $agent,
                $request->amount,
                TransactionName::DebitTransfer,
                [
                    'old_balance' => $player->balanceFloat,
                    'new_balance' => $player->balanceFloat - $request->amount,
                ]
            );
        }

        return redirect()->route('admin.agent.withdraw')->with('success', 'Withdraw status updated successfully!');
    }

    public function statusChangeReject(Request $request, WithDrawRequest $withdraw)
    {
        $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        try {
            $withdraw->update([
                'status' => $request->status,
            ]);

            return redirect()->route('admin.agent.withdraw')->with('success', 'Withdraw status updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function isExistingAgent($userId)
    {
        $user = User::find($userId);

        return $user && $user->hasRole(self::SUB_AGENT_ROLE) ? $user->parent : null;
    }

    private function getAgent()
    {
        return $this->isExistingAgent(Auth::id());
    }
}
