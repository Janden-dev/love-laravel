@extends('layouts.app')

@section('content')
    <div class="pages">
        <section class="page active">
            <div class="app-title">📅 {{ $anniversary ? '编辑纪念日' : '添加纪念日' }}</div>

            <div class="card">
                <form method="POST" action="{{ $anniversary ? route('anniversaries.update', $anniversary) : route('anniversaries.store') }}">
                    @csrf
                    @if($anniversary) @method('PUT') @endif

                    <div class="form-group">
                        <label>标题</label>
                        <input type="text" name="title" value="{{ old('title', $anniversary?->title) }}" placeholder="如：第一次约会" required>
                    </div>

                    <div class="form-group">
                        <label>日期</label>
                        <input type="date" name="date" value="{{ old('date', $anniversary?->date?->toDateString()) }}" required>
                    </div>

                    <div class="form-group">
                        <label>备注（可选）</label>
                        <textarea name="note" placeholder="记录当天的故事...">{{ old('note', $anniversary?->note) }}</textarea>
                    </div>

                    <div class="modal-btns" style="display:flex;gap:10px;">
                        <a href="{{ route('anniversaries.index') }}" class="btn btn-secondary" style="flex:1;">取消</a>
                        <button type="submit" class="btn btn-primary" style="flex:1;">保存</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection
