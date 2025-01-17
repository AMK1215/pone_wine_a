<?php

namespace App\Http\Controllers\Admin\TransferLog;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TransferLogController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function index()
    {
        $this->authorize('transfer_log', User::class);
        $agent = $this->getAgent() ?? Auth::user();

        $transferLogs = $agent->transactions()->with('targetUser')
            ->whereIn('transactions.type', ['withdraw', 'deposit'])
            ->whereIn('transactions.name', ['credit_transfer', 'debit_transfer'])
            ->orderby('transactions.id', 'desc')->get();

        return view('admin.trans_log.index', compact('transferLogs'));
    }

    public function transferLog($id)
    {
        abort_if(
            Gate::denies('make_transfer') || ! $this->ifChildOfParent(request()->user()->id, $id),
            Response::HTTP_FORBIDDEN,
            '403 Forbidden | You cannot access this page because you do not have permission'
        );
        $agent = $this->getAgent() ?? Auth::user();

        $transferLogs = $agent->transactions()->with('targetUser')
            ->whereIn('transactions.type', ['withdraw', 'deposit'])
            ->whereIn('transactions.name', ['credit_transfer', 'debit_transfer'])
            ->where('target_user_id', $id)->orderBy('transactions.id', 'desc')->paginate();

        return view('admin.trans_log.detail', compact('transferLogs'));
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

    //     public function getTopWithdrawals()
    // {
    //     // Retrieve the top 10 withdrawals
    //     $topWithdrawals = DB::table('transactions')
    //         ->join('users', 'transactions.payable_id', '=', 'users.id') // Join with users to get the player's name
    //         ->where('transactions.name', 'debit_transfer') // Only withdrawals
    //         ->where('transactions.confirmed', true) // Ensure the withdrawal is confirmed
    //         ->select('users.name as player_name', 'transactions.amount as withdraw_amount') // Select player's name and withdrawal amount
    //         ->orderByDesc('transactions.amount') // Order by withdrawal amount in descending order
    //         ->limit(10) // Limit to top 10
    //         ->get();

    //     return view('admin.withdraw_top_ten', compact('topWithdrawals'));
    // }
    public function getTopWithdrawals()
    {
        // Retrieve the top 10 withdrawals
        $topWithdrawals = DB::table('transactions')
            ->join('users', 'transactions.payable_id', '=', 'users.id') // Join with users to get the player's name
            ->where('transactions.name', 'debit_transfer') // Only withdrawals
            ->where('transactions.confirmed', true) // Ensure the withdrawal is confirmed
            ->select('users.name as player_name', 'transactions.amount as withdraw_amount', 'transactions.created_at') // Select required fields
            ->orderByDesc('transactions.amount') // Order by withdrawal amount in descending order
            ->orderByDesc('transactions.created_at') // Get the latest transactions
            ->limit(10) // Limit to top 10
            ->get();

        return view('admin.withdraw_top_ten', compact('topWithdrawals'));
    }
}
