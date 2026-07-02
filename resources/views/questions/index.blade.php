@extends('layouts.app')

@section('content')
    @php
        $lang = session('name_lang', 'en');
    @endphp

    <div class="pages">
        <section class="page active">
            <div class="app-title">💬 问答</div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <!-- 提问卡片 -->
            <div class="card">
                <div class="q-section-title">✍️ 问对方一个问题</div>
                <form method="POST" action="{{ route('questions.store') }}">
                    @csrf
                    <div class="form-group">
                        <textarea name="question" placeholder="想问他/她什么呢…" required maxlength="500" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">💌 发送问题</button>
                </form>
            </div>

            <!-- 对方问我的（待回答） -->
            @if($incoming->whereNull('answered_at')->count())
                <div class="q-section-title">📩 他/她问我</div>
                @foreach($incoming->whereNull('answered_at') as $q)
                    @php $askerName = $lang === 'cn' ? $q->fromUser->my_name : $q->fromUser->my_english_name; @endphp
                    <div class="card q-item q-incoming">
                        <div class="q-header">
                            <span class="q-asker">{{ $askerName }}</span>
                            <span class="q-time">{{ $q->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="q-text">{{ $q->question }}</div>
                        <form method="POST" action="{{ route('questions.answer', $q) }}" class="q-answer-form">
                            @csrf
                            <textarea name="answer" placeholder="写回答…" required maxlength="1000" rows="2"></textarea>
                            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">💕 回答</button>
                        </form>
                    </div>
                @endforeach
            @endif

            <!-- 已回答的问题（混合：我问的 + 对方已回答的） -->
            @php
                $answered = $incoming->whereNotNull('answered_at')->merge(
                    $outgoing->whereNotNull('answered_at')
                )->sortByDesc('answered_at');
            @endphp

            @if($answered->count())
                <div class="q-section-title">✅ 已问答</div>
                @foreach($answered as $q)
                    @php
                        $asker = $q->fromUser;
                        $askerName = $lang === 'cn' ? $asker->my_name : $asker->my_english_name;
                        $isMine = $q->from_user_id === Auth::id();
                    @endphp
                    <div class="card q-item q-answered">
                        <div class="q-header">
                            <span class="q-asker">{{ $askerName }} 问：</span>
                            <span class="q-time">{{ $q->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="q-text">{{ $q->question }}</div>
                        <div class="q-answer">
                            <span class="q-answer-label">💕 回答：</span>
                            <span>{{ $q->answer }}</span>
                        </div>
                        @if($isMine)
                            <form method="POST" action="{{ route('questions.destroy', $q) }}" class="q-del-form">
                                @csrf @method('DELETE')
                                <button type="submit" class="q-del-btn" onclick="return confirm('删除这条问答？')">🗑️</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endif

            <!-- 我发出但未回答的 -->
            @php $pendingOutgoing = $outgoing->whereNull('answered_at'); @endphp
            @if($pendingOutgoing->count())
                <div class="q-section-title">⏳ 等待回答</div>
                @foreach($pendingOutgoing as $q)
                    @php $target = $q->toUser; $taName = $lang === 'cn' ? $target->my_name : $target->my_english_name; @endphp
                    <div class="card q-item q-pending">
                        <div class="q-header">
                            <span class="q-asker">我 → {{ $taName }}</span>
                            <span class="q-time">{{ $q->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="q-text">{{ $q->question }}</div>
                        <div class="q-waiting">⏳ 等待对方回答…</div>
                        <form method="POST" action="{{ route('questions.destroy', $q) }}" class="q-del-form">
                            @csrf @method('DELETE')
                            <button type="submit" class="q-del-btn" onclick="return confirm('删除这条问题？')">🗑️</button>
                        </form>
                    </div>
                @endforeach
            @endif

            <div style="height:40px;"></div>
        </section>
    </div>
@endsection
