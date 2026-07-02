<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    const START_DATE = '2026-06-28';

    private function profile(): array
    {
        $user = Auth::user();
        return [
            'myName' => $user->my_name ?? '闫麟飞',
            'myEnglishName' => $user->my_english_name ?? 'janden',
            'partnerName' => $user->partner_name ?? '徐立冉',
            'partnerEnglishName' => $user->partner_english_name ?? 'Larry',
            'bio' => $user->bio ?? '我们的故事，从 2026 年 6 月 28 日开始书写 💕',
        ];
    }

    public function show()
    {
        $start = Carbon::parse(self::START_DATE)->startOfDay();
        $days = max(0, $start->diffInDays(Carbon::today(), false));
        $photos = Photo::orderByDesc('created_at')->get();
        $profile = $this->profile();

        return view('about', compact('days', 'photos', 'profile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'myName' => 'required|string|max:100',
            'myEnglishName' => 'nullable|string|max:100',
            'partnerName' => 'required|string|max:100',
            'partnerEnglishName' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:2000',
        ]);

        Auth::user()->update([
            'my_name' => $validated['myName'],
            'my_english_name' => $validated['myEnglishName'] ?? 'janden',
            'partner_name' => $validated['partnerName'],
            'partner_english_name' => $validated['partnerEnglishName'] ?? 'Larry',
            'bio' => $validated['bio'],
        ]);

        return redirect()->route('about')->with('success', '资料已更新 💕');
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('photo');
        $stored = $file->store('uploads', ['disk' => 'private_uploads']);

        Photo::create([
            'user_id' => Auth::id(),
            'filename' => basename($stored),
            'caption' => '',
            'taken_at' => Carbon::today(),
        ]);

        return back()->with('success', '照片已上传 💕');
    }

    public function deletePhoto(Photo $photo)
    {
        Storage::disk('private_uploads')->delete('uploads/' . $photo->filename);
        $photo->delete();

        return back()->with('success', '照片已删除');
    }

    public function servePhoto(Photo $photo)
    {
        $path = 'uploads/' . $photo->filename;

        if (! Storage::disk('private_uploads')->exists($path)) {
            abort(404);
        }

        return Storage::disk('private_uploads')->response($path);
    }
}
