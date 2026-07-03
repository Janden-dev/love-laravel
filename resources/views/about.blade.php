@extends('layouts.app')

@section('content')
    @php
        $lang = session('name_lang', 'en');
        $me = $lang === 'cn' ? ($profile['myName'] ?? '闫麟飞') : ($profile['myEnglishName'] ?? 'janden');
        $ta = $lang === 'cn' ? ($profile['partnerName'] ?? '徐立冉') : ($profile['partnerEnglishName'] ?? 'Larry');
    @endphp

    <div class="pages">
        <section class="page active">
            <div class="app-title">👤 关于我们</div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card">
                <div class="about-avatars">
                    <div class="avatar">🙋</div>
                    <div class="about-heart">❤️</div>
                    <div class="avatar">🧑</div>
                </div>

                <div class="profile-row">
                    <span class="label">我的昵称</span>
                    <span class="val">{{ $profile['myName'] ?? '闫麟飞' }}</span>
                </div>
                <div class="profile-row">
                    <span class="label">我的英文名</span>
                    <span class="val">{{ $profile['myEnglishName'] ?? 'janden' }}</span>
                </div>
                <div class="profile-row">
                    <span class="label">Ta 的名字</span>
                    <span class="val">{{ $profile['partnerName'] ?? '徐立冉' }}</span>
                </div>
                <div class="profile-row">
                    <span class="label">英文名</span>
                    <span class="val">{{ $profile['partnerEnglishName'] ?? 'Larry' }}</span>
                </div>
                <div class="profile-row">
                    <span class="label">恋爱开始</span>
                    <span class="val">2026.06.28</span>
                </div>
                <div class="profile-row">
                    <span class="label">已相恋</span>
                    <span class="val">{{ $days }} 天</span>
                </div>

                <div class="bio-box">{{ $profile['bio'] ?? '我们的故事，从 2026 年 6 月 28 日开始书写 💕' }}</div>

                <div class="modal-btns" style="margin-top:18px;">
                    <button type="button" class="btn btn-primary" style="width:100%;" onclick="document.getElementById('profileForm').style.display='block';this.style.display='none';">✏️ 编辑资料</button>
                </div>

                <form id="profileForm" method="POST" action="{{ route('about.update') }}" style="display:none;margin-top:14px;">
                    @csrf
                    <div class="form-group">
                        <label>我的昵称</label>
                        <input type="text" name="myName" value="{{ $profile['myName'] ?? '闫麟飞' }}">
                    </div>
                    <div class="form-group">
                        <label>我的英文名</label>
                        <input type="text" name="myEnglishName" value="{{ $profile['myEnglishName'] ?? 'janden' }}">
                    </div>
                    <div class="form-group">
                        <label>Ta 的名字</label>
                        <input type="text" name="partnerName" value="{{ $profile['partnerName'] ?? '徐立冉' }}">
                    </div>
                    <div class="form-group">
                        <label>英文名</label>
                        <input type="text" name="partnerEnglishName" value="{{ $profile['partnerEnglishName'] ?? 'Larry' }}">
                    </div>
                    <div class="form-group">
                        <label>简介</label>
                        <textarea name="bio">{{ $profile['bio'] ?? '我们的故事，从 2026 年 6 月 28 日开始书写 💕' }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">保存资料</button>
                </form>
            </div>

            <div class="section-title">📸 照片墙
                <span style="flex:1;"></span>
                <button type="button" class="add-btn" id="photoDateToggle" title="选择日期">📅</button>
                <form id="photoForm" method="POST" action="{{ route('photos.store') }}" enctype="multipart/form-data" style="display:inline;">
                    @csrf
                    <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;">
                    <label for="photoInput" class="add-btn">+</label>
                </form>
            </div>

            {{-- 日期选择弹窗 --}}
            <div class="photo-date-popup" id="photoDatePopup" style="display:none;">
                <div class="photo-date-popup-card">
                    <div class="photo-date-popup-title">📅 选择日期</div>
                    <form method="GET" action="{{ route('about') }}" autocomplete="off">
                        <input type="date" name="photo_date" value="{{ $photoDate ?? $availableDates->first() }}"
                               min="{{ $availableDates->last() }}" max="{{ $availableDates->first() }}"
                               class="photo-date-input">
                        <div class="photo-date-popup-actions">
                            <button type="submit" class="btn btn-primary" style="flex:1;">确认</button>
                            <button type="button" class="btn btn-secondary" onclick="closePhotoDatePicker()">取消</button>
                        </div>
                    </form>
                </div>
            </div>

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
                        <div class="empty" style="grid-column:1/-1;padding:20px 0;">
                            <p>点击右上角「+」上传你们的甜蜜瞬间 📸</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
    document.getElementById('photoDateToggle')?.addEventListener('click', function () {
        document.getElementById('photoDatePopup').style.display = 'flex';
    });
    window.closePhotoDatePicker = function () {
        document.getElementById('photoDatePopup').style.display = 'none';
    };
    </script>
@endpush

@section('lightbox')
    <div class="lightbox" id="lightbox">
        <img id="lightboxImg" src="" alt="">
        <div class="cap" id="lightboxCap"></div>
    </div>
@endsection
