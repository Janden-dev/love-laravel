<?php

namespace App\Http\Controllers;

use App\Models\Diary;
use App\Models\MissYou;
use App\Models\Photo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $start = Carbon::parse('2026-06-28')->startOfDay();
        $now = Carbon::now()->startOfDay();
        $days = max(0, $start->diffInDays($now, false));

        // Get latest diary from each user
        $users = User::orderBy('id')->get();
        $latestDiaries = [];
        foreach ($users as $user) {
            $latest = Diary::byUser($user->id)->orderByDesc('entry_date')->first();
            if ($latest) {
                $latestDiaries[$user->id] = [
                    'user' => $user,
                    'diary' => $latest,
                ];
            }
        }

        $photos = Photo::orderByDesc('created_at')->take(6)->get();

        // Miss-you counts for today
        $today = Carbon::today()->toDateString();
        $myMissCount = MissYou::where('user_id', Auth::id())->where('date', $today)->value('count') ?? 0;
        $partner = User::where('id', '!=', Auth::id())->first();
        $partnerMissCount = $partner
            ? (MissYou::where('user_id', $partner->id)->where('date', $today)->value('count') ?? 0)
            : 0;

        return view('home', compact('days', 'start', 'latestDiaries', 'photos', 'users', 'myMissCount', 'partnerMissCount'));
    }

    public function missYou(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $userId = Auth::id();

        $record = MissYou::firstOrCreate(
            ['user_id' => $userId, 'date' => $today],
            ['count' => 0]
        );

        $record->increment('count');

        // Get partner's count to return
        $partner = User::where('id', '!=', $userId)->first();
        $partnerMissCount = $partner
            ? (MissYou::where('user_id', $partner->id)->where('date', $today)->value('count') ?? 0)
            : 0;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'myCount' => $record->fresh()->count,
                'partnerCount' => $partnerMissCount,
            ]);
        }

        return back();
    }
}
