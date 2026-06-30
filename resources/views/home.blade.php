@extends('layouts.app')

@section('content')
    @php
        $lang = session('name_lang', 'en');
        $me = $lang === 'cn' ? '闫麟飞' : 'janden';
        $ta = $lang === 'cn' ? '徐立冉' : 'Larry';
    @endphp

    <div class="pages">
        <section class="page active">
            <div class="app-title">💕 {{ $me }} &amp; {{ $ta }}<small>我们的恋爱纪念</small>
            </div>

            <div class="name-toggle-row">
                <form method="POST" action="{{ route('toggle.name-lang') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="name-toggle">🔤 切换中英文名</button>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card timer-card">
                <div class="heart-big">❤️</div>
                <div class="timer-days">{{ $days }}</div>
                <div class="timer-text">我们已经在一起 <span>{{ $days }}</span> 天啦 💕</div>
                <div class="timer-date">📅 {{ $start->format('Y.m.d') }} → forever</div>
                <div class="timer-detail">愿往后的每一天都甜甜的 🍬</div>

                @php
                    $milestones = [100, 200, 365, 520, 1000];
                    $next = collect($milestones)->first(fn($m) => $m > $days);
                @endphp
                @if($next)
                    @php
                        $label = $next === 365 ? '1 周年' : ($next === 520 ? '520 表白日' : $next . ' 天');
                    @endphp
                    <div class="milestone">🎉 距离「{{ $label }}」还有 {{ $next - $days }} 天</div>
                @endif
            </div>

            <div class="section-title">📸 照片墙</div>
            <div class="card">
                <div class="photo-grid">
                    @forelse($photos as $photo)
                        <div class="ph">
                            <img src="{{ $photo->url() }}" alt="" data-lightbox data-src="{{ $photo->url() }}" data-caption="{{ $photo->taken_at?->format('Y.m.d') ?? '' }}">
                            <form action="{{ route('photos.destroy', $photo) }}" method="POST" id="photo-del-{{ $photo->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="del" onclick="confirmDelete('删除这张照片？', 'photo-del-{{ $photo->id }}')">×</button>
                            </form>
                        </div>
                    @empty
                        <div class="empty" style="grid-column:1/-1;padding:20px 0;"><p>还没有照片，去「关于」页上传吧 📸</p></div>
                    @endforelse
                </div>
            </div>

            <div class="section-title">📝 最新日记</div>
            <div class="card">
                @if($latestDiary)
                    <div class="d-date">📅 {{ $latestDiary->entry_date->format('Y.m.d') }} {{ $latestDiary->mood }}</div>
                    <div class="diary-text">{{ $latestDiary->text }}</div>
                @else
                    <div class="empty" style="padding:20px 0;">
                        <p>还没有日记，去「日记」页写第一篇吧 📖</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@section('lightbox')
    <div class="lightbox" id="lightbox">
        <img id="lightboxImg" src="" alt="">
        <div class="cap" id="lightboxCap"></div>
    </div>
@endsection
