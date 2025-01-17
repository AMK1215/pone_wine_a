<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Webhook\Result;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{
    protected const SUB_AGENT_ROlE = 'Sub Agent';

    public function index()
    {
        $adminId = auth()->id();

        $report = Result::select(
            DB::raw('SUM(total_bet_amount) as total_bet_amount'),
            DB::raw('SUM(win_amount) as total_win_amount'),
            DB::raw('SUM(net_win) as total_net_win'),
            DB::raw('COUNT(*) as total_games'),
            'players.name as player_name',
            'agents.name as agent_name',
            'players.id as user_id'
        )
            ->join('users as players', 'results.user_id', '=', 'players.id')
            ->join('users as agents', 'players.agent_id', '=', 'agents.id')
            ->groupBy('players.name', 'agents.name', 'players.id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.reports.index', compact('report'));
    }

    public function getReportDetails($player_id)
    {
        $details = Result::where('user_id', $player_id)
            ->join('users', 'results.user_id', '=', 'users.id')
            ->select('results.*', 'users.name as user_name')
            ->get();

        // Calculate totals
        $totalBet = $details->sum('total_bet_amount');
        $totalWin = $details->sum('win_amount');
        $totalNetWin = $details->sum('net_win');

        return view('admin.reports.detail', compact('details', 'totalBet', 'totalWin', 'totalNetWin'));
    }

    public function Reportindex()
    {
        $adminId = auth()->id(); // Get the authenticated admin's ID

        $report = Result::select(
            DB::raw('SUM(results.total_bet_amount) as total_bet_amount'),
            DB::raw('SUM(results.win_amount) as total_win_amount'),
            DB::raw('SUM(results.net_win) as total_net_win'),
            DB::raw('COUNT(results.id) as total_games'),
            'players.name as player_name',
            'agents.name as agent_name',
            'players.id as user_id'
        )
            ->join('users as players', 'results.user_id', '=', 'players.id') // Join players with results
            ->join('users as agents', 'players.agent_id', '=', 'agents.id') // Join agents with players
            ->where('agents.agent_id', $adminId) // Filter agents belonging to the authenticated admin
            ->groupBy('players.name', 'agents.name', 'players.id') // Group by player name, agent name, and player ID
            ->paginate(10) // Paginate results to show 10 per page
            ->withQueryString(); // Preserve query string in pagination links

        // Calculate totals
        $totalBet = $report->sum('total_bet_amount');
        $totalWin = $report->sum('total_win_amount');
        $totalNetWin = $report->sum('total_net_win');

        return view('admin.reports.index_report', compact('report', 'totalBet', 'totalWin', 'totalNetWin'));
    }

    public function playerDetails($playerId)
    {
        $details = Result::where('user_id', $playerId)
            ->join('users', 'results.user_id', '=', 'users.id')
            ->select('results.*', 'users.name as user_name')
            ->get();
        //->paginate(10); // Paginate results to show 10 per page
        // Calculate totals
        $totalBet = $details->sum('total_bet_amount');
        $totalWin = $details->sum('win_amount');
        $totalNetWin = $details->sum('net_win');

        return view('admin.reports.agent_player_details', compact('details', 'totalBet', 'totalWin', 'totalNetWin'));
    }

    public function AgentReportindex()
    {
        $agent = $this->getAgent() ?? Auth::user();

        $report = Result::select(
            DB::raw('SUM(results.total_bet_amount) as total_bet_amount'),
            DB::raw('SUM(results.win_amount) as total_win_amount'),
            DB::raw('SUM(results.net_win) as total_net_win'),
            DB::raw('COUNT(results.id) as total_games'),
            'players.name as player_name',
            'agents.name as agent_name',
            'players.id as user_id',
            'players.user_name as user_name'
        )
            ->join('users as players', 'results.user_id', '=', 'players.id') // Join players with results
            ->join('users as agents', 'players.agent_id', '=', 'agents.id') // Join agents with players
            ->where('agents.id', $agent->id) // Filter data for the authenticated agent only
            ->groupBy('players.name', 'agents.name', 'players.id', 'players.user_name') // Group by player name, agent name, and player ID
            ->paginate(10) // Paginate results to show 10 per page
            ->withQueryString(); // Preserve query string in pagination links

        // Calculate totals
        $totalBet = $report->sum('total_bet_amount');
        $totalWin = $report->sum('total_win_amount');
        $totalNetWin = $report->sum('total_net_win');

        return view('admin.reports.agent_index_report', compact('report', 'totalBet', 'totalWin', 'totalNetWin'));
    }

    public function getTransactionDetails($tranId)
    {
        $operatorId = 'delightMMK';

        $url = 'https://api.sm-sspi-prod.com/api/opgateway/v1/op/GetTransactionDetails';

        // Generate the RequestDateTime in UTC
        $requestDateTime = Carbon::now('UTC')->format('Y-m-d H:i:s');

        // Generate the signature using MD5 hashing
        $secretKey = '1OMJXOf88RHKpcuT';
        $functionName = 'GetTransactionDetails';
        $signatureString = $functionName.$requestDateTime.$operatorId.$secretKey;
        $signature = md5($signatureString);

        // Prepare request payload
        $payload = [
            'OperatorId' => $operatorId,
            'RequestDateTime' => $requestDateTime,
            'Signature' => $signature,
            'TranId' => $tranId,
        ];

        try {
            // Make the POST request to the API endpoint
            $response = Http::post($url, $payload);

            // Check if the response is successful
            if ($response->successful()) {
                return $response->json(); // Return the response data as JSON
            } else {
                Log::error('Failed to get transaction details', ['response' => $response->body()]);

                return response()->json(['error' => 'Failed to get transaction details'], 500);
            }
        } catch (\Exception $e) {
            Log::error('API request error', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'API request error'], 500);
        }
    }

    public function getResultsForUser($userName)
    {
        // Fetch results data for the given user_name
        $results = DB::table('results')
            ->join('users', 'results.user_id', '=', 'users.id') // Join results with users table
            ->where('users.user_name', $userName) // Filter by user_name
            ->select(
                'results.id',
                'results.player_name',
                'results.game_provide_name',
                'results.game_name',
                'results.operator_id',
                'results.request_date_time',
                'results.signature',
                'results.player_id',
                'results.currency',
                'results.round_id',
                'results.bet_ids',
                'results.result_id',
                'results.game_code',
                'results.total_bet_amount',
                'results.win_amount',
                'results.net_win',
                'results.tran_date_time',
                'users.name as user_name'
            )
            ->get();

        // Return the data as JSON
        return response()->json($results);
    }

    public function getResultsForOnlyUser($userName)
    {
        // Fetch results data for the given user_name
        $results = DB::table('results')
            ->join('users', 'results.user_id', '=', 'users.id') // Join results with users table
            ->where('users.user_name', $userName) // Filter by user_name
            ->select(
                'results.id',
                'results.player_name',
                'results.game_provide_name',
                'results.game_name',
                'results.operator_id',
                'results.request_date_time',
                'results.signature',
                'results.player_id',
                'results.currency',
                'results.round_id',
                'results.bet_ids',
                'results.result_id',
                'results.game_code',
                'results.total_bet_amount',
                'results.win_amount',
                'results.net_win',
                'results.tran_date_time',
                'users.name as user_name'
            )
            ->get();

        // Return the data as JSON
        return view('admin.reports.v3_index', compact('results'));
    }

    public function GetResult()
    {
        return view('admin.reports.find_by_username_index');
    }

    // public function FindByUserName(Request $request)
    // {
    //     // Validate the incoming request
    //     $request->validate([
    //         'user_name' => 'required|string|exists:users,user_name', // Ensure 'user_name' is provided and exists in the 'users' table
    //     ]);

    //     // Fetch the user_name from the request
    //     $userName = $request->input('user_name');

    //     // Fetch results data for the given user_name
    //     $results = DB::table('results')
    //         ->join('users', 'results.user_id', '=', 'users.id') // Join results with users table
    //         ->where('users.user_name', $userName) // Filter by user_name
    //         ->select(
    //             'results.id',
    //             'results.player_name',
    //             'results.game_provide_name',
    //             'results.game_name',
    //             'results.operator_id',
    //             'results.request_date_time',
    //             'results.signature',
    //             'results.player_id',
    //             'results.currency',
    //             'results.round_id',
    //             'results.bet_ids',
    //             'results.result_id',
    //             'results.game_code',
    //             'results.total_bet_amount',
    //             'results.win_amount',
    //             'results.net_win',
    //             'results.tran_date_time',
    //             'users.name as user_name'
    //         )
    //         ->get();

    //     // Return the data to the view
    //     return view('admin.reports.find_by_username_index', compact('results'));
    // }
    public function FindByUserName(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'user_name' => 'required|string|exists:users,user_name', // Ensure 'user_name' is provided and exists in the 'users' table
        ]);

        // Fetch the user_name from the request
        $userName = $request->input('user_name');

        // Fetch results data for the given user_name, ordered by tran_date_time in descending order
        $results = DB::table('results')
            ->join('users', 'results.user_id', '=', 'users.id') // Join results with users table
            ->where('users.user_name', $userName) // Filter by user_name
            ->select(
                'results.id',
                'results.player_name',
                'results.game_provide_name',
                'results.game_name',
                'results.operator_id',
                'results.request_date_time',
                'results.signature',
                'results.player_id',
                'results.currency',
                'results.round_id',
                'results.bet_ids',
                'results.result_id',
                'results.game_code',
                'results.total_bet_amount',
                'results.win_amount',
                'results.net_win',
                'results.tran_date_time',
                'users.name as user_name'
            )
            ->orderByDesc('results.tran_date_time') // Order by transaction date and time in descending order
            ->get();

        // Return the data to the view
        return view('admin.reports.find_by_username_index', compact('results'));
    }

    public function deleteResult($id)
    {
        try {
            // Find the result by ID
            $result = DB::table('results')->where('id', $id)->first();

            if (! $result) {
                return redirect()->back()->with('error', 'Result not found.');
            }

            // Delete the result
            DB::table('results')->where('id', $id)->delete();

            return redirect()->route('admin.ResultSearchIindex')->with('success', 'Result deleted successfully.');

            //return redirect()->back()->with('success', 'Result deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function deleteMultiple(Request $request)
    {
        try {
            // Check if IDs are provided
            if (! $request->has('ids') || empty($request->ids)) {
                return redirect()->back()->with('error', 'No results selected for deletion.');
            }

            // Delete all selected results
            DB::table('results')->whereIn('id', $request->ids)->delete();

            return redirect()->route('admin.ResultSearchIindex')->with('success', 'Selected results deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: '.$e->getMessage());
        }
    }

    public function BoReport()
    {
        $response = Http::post('https://agdashboard.pro/proxy-to-bo/', [
            'username' => 'delightMMK',
            'password' => '123456',
        ]);

        if ($response->ok()) {
            return $response->body(); // Display the response from the backend
        } else {
            return 'Error: Unable to login';
        }
    }

    // for senior
    public function getAllResults()
    {
        // Retrieve all results with pagination (10 per page)
        $results = DB::table('results')
            ->join('users', 'results.user_id', '=', 'users.id') // Join with users to get related user data
            ->select(
                'results.*',
                'users.name as player_name', // Include player's name from users table
                'users.user_name as player_id'
            )
            ->paginate(200); // Paginate results with 10 per page

        // Pass the results to the view
        return view('admin.reports.senior.result_index', compact('results'));
    }

    public function deleteResults(Request $request)
    {
        // Validate the request
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Retrieve the date range
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Delete the results within the specified date range
        DB::table('results')
            ->whereBetween('tran_date_time', [$startDate, $endDate])
            ->delete();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Results deleted successfully.');
    }

    public function getAllBets()
    {
        // Retrieve all results with pagination (10 per page)
        $results = DB::table('bets')
            ->join('users', 'bets.user_id', '=', 'users.id') // Join with users to get related user data
            ->select(
                'bets.*',
                'users.name as player_name', // Include player's name from users table
                //'users.user_name as player_id'
            )
            ->paginate(10); // Paginate results with 10 per page

        // Pass the results to the view
        return view('admin.reports.senior.bet_index', compact('results'));
    }

    public function deleteBets(Request $request)
    {
        // Validate the request
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Retrieve the date range
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Delete the results within the specified date range
        DB::table('bets')
            ->whereBetween('tran_date_time', [$startDate, $endDate])
            ->delete();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Bets deleted successfully.');
    }

    public function getAllJili()
    {
        // Retrieve all results with pagination (10 per page)
        $results = DB::table('bet_n_results')
            ->join('users', 'bet_n_results.user_id', '=', 'users.id') // Join with users to get related user data
            ->select(
                'bet_n_results.*',
                'users.name as player_name', // Include player's name from users table
                'users.user_name as player_id'
            )
            ->paginate(10); // Paginate results with 10 per page

        // Pass the results to the view
        return view('admin.reports.senior.bet_n_result_index', compact('results'));
    }

    public function deleteJili(Request $request)
    {
        // Validate the request
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // Retrieve the date range
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        // Delete the results within the specified date range
        DB::table('bet_n_results')
            ->whereBetween('tran_date_time', [$startDate, $endDate])
            ->delete();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Bets deleted successfully.');
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
