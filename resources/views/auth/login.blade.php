@extends('layouts.app')

@section('content')
    <div class="login-wrap">
        <div class="login-card">
            <h1>💕 Larry & Me</h1>
            <p>恋爱纪念日 · 专属入口</p>

            @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="janden" autofocus>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" placeholder="••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">进入我们的世界</button>
            </form>

            <p style="margin-top:18px;font-size:12px;color:var(--text-light);">默认账号：janden / larry2026</p>
        </div>
    </div>
@endsection
