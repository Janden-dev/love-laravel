<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 对方问我的（待回答）
        $incoming = Question::with('fromUser')
            ->askedTo($userId)
            ->orderByDesc('created_at')
            ->get();

        // 我问对方的（包括已回答和未回答）
        $outgoing = Question::with('toUser')
            ->askedBy($userId)
            ->orderByDesc('created_at')
            ->get();

        // 未读的待回答数量
        $unansweredCount = $incoming->whereNull('answered_at')->count();

        return view('questions.index', compact('incoming', 'outgoing', 'unansweredCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        $partner = User::where('id', '!=', Auth::id())->firstOrFail();

        Question::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $partner->id,
            'question' => $validated['question'],
        ]);

        return redirect()->route('questions.index')->with('success', '问题已发送 💌');
    }

    public function answer(Request $request, Question $question)
    {
        // 仅限收信方回答
        if ($question->to_user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'answer' => 'required|string|max:1000',
        ]);

        $question->update([
            'answer' => $validated['answer'],
            'answered_at' => Carbon::now(),
        ]);

        return redirect()->route('questions.index')->with('success', '回答已发送 💕');
    }

    public function destroy(Question $question)
    {
        // 仅限提问方删除
        if ($question->from_user_id !== Auth::id()) {
            abort(403);
        }

        $question->delete();

        return redirect()->route('questions.index')->with('success', '问题已删除');
    }
}
