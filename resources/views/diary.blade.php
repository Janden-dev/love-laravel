@extends('layouts.app')

@section('content')
    @php
        use Carbon\Carbon;
        $lang = session('name_lang', 'en');
        $year = $current->year;
        $month = $current->month;
        $firstDay = $current->copy()->startOfMonth()->dayOfWeek;
        $daysInMonth = $current->daysInMonth;
        $today = Carbon::today()->toDateString();
        $weekdays = ['日','一','二','三','四','五','六'];
        $isOwnDiary = $viewUser->id === Auth::id();
        $partnerName = $lang === 'cn' ? $partnerUser->my_name : $partnerUser->my_english_name;
    @endphp

    <div class="pages">
        <section class="page active">
            <div class="app-title">📖 心情日记</div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <!-- 对方日记切换按钮 -->
            <div class="name-toggle-row">
                <a href="{{ route('diary.index', [
                    'user_id' => $isOwnDiary ? $partnerUser->id : Auth::id(),
                    'year' => $year,
                    'month' => $month,
                    'date' => $selectedDate,
                ]) }}" class="name-toggle">
                    {{ $isOwnDiary ? "👀 查看{$partnerName}的日记" : '◀️ 返回我的日记' }}
                </a>
            </div>

            <div class="card">
                <div class="cal-header">
                    <a href="{{ route('diary.index', ['user_id' => $viewUser->id, 'year' => $current->copy()->subMonth()->year, 'month' => $current->copy()->subMonth()->month, 'date' => $selectedDate]) }}">‹</a>
                    <span>{{ $year }} 年 {{ $month }} 月</span>
                    <a href="{{ route('diary.index', ['user_id' => $viewUser->id, 'year' => $current->copy()->addMonth()->year, 'month' => $current->copy()->addMonth()->month, 'date' => $selectedDate]) }}">›</a>
                </div>

                <div class="cal-grid">
                    @foreach($weekdays as $wd)
                        <div class="wd">{{ $wd }}</div>
                    @endforeach

                    @for($i = 0; $i < $firstDay; $i++)
                        <div class="day empty-cell"></div>
                    @endfor

                    @for($d = 1; $d <= $daysInMonth; $d++)
                        @php
                            $ds = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $isToday = $ds === $today;
                            $isSel = $ds === $selectedDate;
                            $has = isset($diaries[$ds]);
                        @endphp
                        <a href="{{ route('diary.index', ['user_id' => $viewUser->id, 'year' => $year, 'month' => $month, 'date' => $ds]) }}"
                           class="day {{ $isToday ? 'today' : '' }} {{ $isSel ? 'selected' : '' }}">
                            {{ $d }}
                            @if($has)
                                <span class="dot">❤️</span>
                            @endif
                        </a>
                    @endfor
                </div>
            </div>

            <div class="card diary-view">
                @php
                    $isToday = $selectedDate === $today;
                    $canEdit = $selectedDate <= $today && $isOwnDiary;
                    $selectedCarbon = Carbon::parse($selectedDate);
                @endphp

                <div class="d-date">
                    📅 {{ $selectedCarbon->format('Y.m.d') }} {{ $isToday ? '（今天）' : '' }}
                    @if(!$isOwnDiary)
                        <span class="viewing-tag">
                            {{ $lang === 'cn' ? $viewUser->my_name : $viewUser->my_english_name }} 的日记
                        </span>
                    @endif
                </div>

                @if($selectedDiary)
                    <div class="diary-mood">{{ $selectedDiary->mood }}</div>
                    <div class="diary-text">{{ $selectedDiary->text }}</div>
                    @if($canEdit)
                        <div class="modal-btns" style="margin-top:14px;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('diaryForm').style.display='block';this.style.display='none';">✏️ 编辑</button>
                        </div>
                    @endif
                @else
                    @if($canEdit)
                        <div class="empty" style="padding:20px;">
                            <div class="icon">📝</div>
                            <p>这一天还没有记录<br>写点什么吧~</p>
                        </div>
                    @else
                        <div class="empty" style="padding:20px;">
                            <div class="icon">🗓️</div>
                            @if($isOwnDiary)
                                <p>未来的日子，等到了再记录吧 ✨</p>
                            @else
                                <p>{{ $lang === 'cn' ? $viewUser->my_name : $viewUser->my_english_name }} 这一天还没有写日记 ✨</p>
                            @endif
                        </div>
                    @endif
                @endif

                @if($canEdit)
                    <form id="diaryForm" method="POST" action="{{ route('diary.store') }}" style="display:{{ $selectedDiary ? 'none' : 'block' }};margin-top:10px;">
                        @csrf
                        <input type="hidden" name="entry_date" value="{{ $selectedDate }}">
                        <input type="hidden" name="mood" id="moodInput" value="{{ $selectedDiary?->mood ?? '😊' }}">

                        <label style="font-size:13px;color:var(--text-light);">心情</label>
                        <div class="mood-picker">
                            @foreach(['😊','🥰','😍','😘','😢','😡','😴','🤔','😎','🥳'] as $m)
                                <span class="{{ ($selectedDiary?->mood ?? '😊') === $m ? 'sel' : '' }}" onclick="pickMood(this, '{{ $m }}')">{{ $m }}</span>
                            @endforeach
                        </div>

                        <div class="form-group" style="margin-top:10px;">
                            <textarea name="text" placeholder="今天发生了什么..." required>{{ $selectedDiary?->text }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;">保存日记</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
