@extends('layouts.app')

@section('content')
    <div class="pages">
        <section class="page active">
            <div class="app-title">📅 纪念日</div>

            <div class="section-title">重要的日子
                <a href="{{ route('anniversaries.create') }}" class="add-btn">+</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @php
                $start = \Carbon\Carbon::parse('2026-06-28')->startOfDay();
            @endphp

            @forelse($anniversaries as $anniv)
                @php
                    $diff = $anniv->date->startOfDay()->diffInDays(\Carbon\Carbon::today(), false);
                    if ($diff > 0) {
                        $cd = "已经过去 {$diff} 天";
                    } elseif ($diff == 0) {
                        $cd = '就是今天！🎊';
                    } else {
                        $cd = '还有 ' . abs($diff) . ' 天';
                    }
                @endphp
                <div class="anniv-item">
                    <h3>{{ $anniv->title }}</h3>
                    <div class="date">📅 {{ $anniv->date->format('Y.m.d') }}</div>
                    <div class="countdown">{{ $cd }}</div>
                    @if($anniv->note)
                        <div class="note">{{ $anniv->note }}</div>
                    @endif
                    <div class="ops">
                        <a href="{{ route('anniversaries.edit', $anniv) }}">编辑</a>
                        <form action="{{ route('anniversaries.destroy', $anniv) }}" method="POST" id="anniv-del-{{ $anniv->id }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="button" onclick="confirmDelete('确定删除这条纪念日吗？', 'anniv-del-{{ $anniv->id }}')">删除</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="empty">
                    <div class="icon">📭</div>
                    <p>还没有纪念日哦~<br>点击右上角「+」记录你们的重要时刻吧 💕</p>
                </div>
            @endforelse
        </section>
    </div>
@endsection
