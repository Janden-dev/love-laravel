<?php

namespace App\Http\Controllers;

use App\Models\Diary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiaryController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->input('year', Carbon::now()->year);
        $month = (int) $request->input('month', Carbon::now()->month);

        $current = Carbon::createFromDate($year, $month, 1);
        $selectedDate = $request->input('date', Carbon::today()->toDateString());

        // Determine which user's diary to view: default to current user
        $viewUserId = (int) $request->input('user_id', Auth::id());
        $viewUser = User::findOrFail($viewUserId);

        $diaries = Diary::byUser($viewUserId)
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->pluck('mood', 'entry_date');

        $selectedDiary = Diary::byUser($viewUserId)
            ->where('entry_date', $selectedDate)
            ->first();

        // Find the partner (the other user) for the toggle button
        $partnerUser = User::where('id', '!=', Auth::id())->first();

        return view('diary', compact('current', 'diaries', 'selectedDate', 'selectedDiary', 'viewUser', 'partnerUser'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'mood' => 'nullable|string|max:32',
            'text' => 'nullable|string|max:5000',
        ]);

        $userId = Auth::id();

        if (empty($validated['text'])) {
            Diary::byUser($userId)->where('entry_date', $validated['entry_date'])->delete();
            return back()->with('success', '日记已删除');
        }

        Diary::updateOrCreate(
            ['user_id' => $userId, 'entry_date' => $validated['entry_date']],
            ['mood' => $validated['mood'] ?? '😊', 'text' => $validated['text']]
        );

        return redirect()->route('diary.index', [
            'year' => Carbon::parse($validated['entry_date'])->year,
            'month' => Carbon::parse($validated['entry_date'])->month,
            'date' => $validated['entry_date'],
        ])->with('success', '日记已保存 💕');
    }
}
