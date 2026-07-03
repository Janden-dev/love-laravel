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

    public function show(Request $request)
    {
        $start = Carbon::parse(self::START_DATE)->startOfDay();
        $days = max(0, $start->diffInDays(Carbon::today(), false));
        $profile = $this->profile();

        // 获取所有有照片的日期，按日期降序
        $availableDates = Photo::selectRaw('DATE(taken_at) as date')
            ->groupBy('date')
            ->orderByDesc('date')
            ->pluck('date');

        // 默认选中最新日期，用户可选择其他日期
        $photoDate = $request->input('photo_date', $availableDates->first());
        $photos = Photo::whereDate('taken_at', $photoDate)
            ->orderByDesc('created_at')
            ->get();

        return view('about', compact('days', 'photos', 'profile', 'availableDates', 'photoDate'));
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
            'photo' => 'required|image|mimes:jpeg,png,gif,webp|max:20480',
        ]);

        $file = $request->file('photo');

        // 自动压缩：最长边缩至 1920px，JPEG 质量 80%
        $compressed = $this->compressImage($file);
        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME) . '.jpg';
        $path = 'uploads/' . $filename;
        Storage::disk('private_uploads')->put($path, $compressed);

        Photo::create([
            'user_id' => Auth::id(),
            'filename' => $filename,
            'caption' => '',
            'taken_at' => Carbon::today(),
        ]);

        return back()->with('success', '照片已上传 💕');
    }

    /**
     * 用 GD 压缩图片：最长边 ≤ 1920px，JPEG 质量 80%
     */
    private function compressImage($file): string
    {
        $maxDim = 1920;
        $quality = 80;

        // 根据 mime 类型创建 GD 图像资源
        $mime = $file->getMimeType();
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png'  => @imagecreatefrompng($file->getRealPath()),
            'image/gif'  => @imagecreatefromgif($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default      => @imagecreatefromjpeg($file->getRealPath()),
        };

        if (!$source) {
            // GD 打不开就走原始文件
            return file_get_contents($file->getRealPath());
        }

        $origW = imagesx($source);
        $origH = imagesy($source);

        // 计算缩放比例
        $ratio = min($maxDim / $origW, $maxDim / $origH, 1);
        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        if ($ratio >= 1) {
            // 原本就不大，直接输出 JPEG
            imagedestroy($source);
            return (string) $file->get();
        }

        $canvas = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($source);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $data = ob_get_clean();
        imagedestroy($canvas);

        return $data;
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
