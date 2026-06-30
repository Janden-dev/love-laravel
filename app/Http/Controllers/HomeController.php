<?php

namespace App\Http\Controllers;

use App\Models\Diary;
use App\Models\Photo;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $start = Carbon::parse('2026-06-28')->startOfDay();
        $now = Carbon::now()->startOfDay();
        $days = max(0, $start->diffInDays($now, false));

        $latestDiary = Diary::orderByDesc('entry_date')->first();
        $photos = Photo::orderByDesc('created_at')->take(6)->get();

        return view('home', compact('days', 'start', 'latestDiary', 'photos'));
    }
}
