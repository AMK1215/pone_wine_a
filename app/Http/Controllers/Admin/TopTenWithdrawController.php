<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\TopTenWithdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopTenWithdrawController extends Controller
{
    public function index()
    {
        $texts = TopTenWithdraw::where('admin_id', auth()->id())->get(); // Fetch banners for the logged-in admin

        return view('admin.top_ten.index', compact('texts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.top_ten.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'player_id' => 'required',
        ]);
        TopTenWithdraw::create([
            'player_id' => $request->player_id,
            'amount' => $request->amount,
            'admin_id' => auth()->id(), // Associate with the authenticated admin

        ]);

        return redirect(route('admin.top-10-withdraws.index'))->with('success', 'New Text Created Successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TopTenWithdraw $text)
    {
        return view('admin.top_ten.show', compact('text'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TopTenWithdraw $text)
    {
        return view('admin.top_ten.edit', compact('text'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TopTenWithdraw $text)
    {
        $request->validate([
            'text' => 'required',
        ]);
        $text->update([
            'text' => $request->text,
        ]);

        return redirect(route('admin.top-10-withdraws.index'))->with('success', 'Marquee Text Updated Successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(TopTenWithdraw $text)
    // {
    //     $text->delete();

    //     return redirect()->back()->with('success', 'TopTenWithdraw  Deleted Successfully.');
    // }
    //     public function destroy(TopTenWithdraw $text)
    // {
    //     if (!$text) {
    //         return redirect()->back()->with('error', 'TopTenWithdraw not found.');
    //     }

    //     try {
    //         $text->delete();
    //         return redirect()->back()->with('success', 'TopTenWithdraw Deleted Successfully.');
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Error deleting TopTenWithdraw: ' . $e->getMessage());
    //     }
    // }

    //     public function destroy(TopTenWithdraw $text)
    // {
    //     try {
    //         $text->delete();
    //         Log::info('Resolved TopTenWithdraw instance:', $text->toArray());
    //         Log::info('TopTenWithdraw deleted successfully.', ['id' => $text->id]);
    //         return redirect()->back()->with('success', 'TopTenWithdraw Deleted Successfully.');
    //     } catch (\Exception $e) {
    //         Log::error('Error deleting TopTenWithdraw: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Error deleting TopTenWithdraw.');
    //     }
    // }

    public function destroy($id)
    {
        // Find the record or throw a 404 if not found
        $text = TopTenWithdraw::findOrFail($id);

        // Log the resolved model for debugging
        Log::info('Deleting TopTenWithdraw:', $text->toArray());

        // Delete the record
        $text->delete();

        // Redirect back with a success message
        return redirect()->back()->with('success', 'TopTenWithdraw Deleted Successfully.');
    }
}
