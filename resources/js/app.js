/* ---------- 主题切换 ---------- */
const themeToggle = document.getElementById('themeToggle');
if (themeToggle) {
    function applyTheme() {
        const dark = localStorage.getItem('love_theme') === 'dark';
        document.body.classList.toggle('dark', dark);
        themeToggle.textContent = dark ? '☀️' : '🌙';
    }
    themeToggle.addEventListener('click', () => {
        const dark = !document.body.classList.contains('dark');
        localStorage.setItem('love_theme', dark ? 'dark' : 'light');
        applyTheme();
    });
    applyTheme();
}

/* ---------- 底部 Tab 激活状态 ---------- */
const tabs = document.querySelectorAll('.tab');
const currentPath = location.pathname;
tabs.forEach(tab => {
    const href = tab.getAttribute('href');
    if (href && currentPath === href) {
        tab.classList.add('active');
    } else {
        tab.classList.remove('active');
    }
});

/* ---------- Lightbox ---------- */
const lightbox = document.getElementById('lightbox');
if (lightbox) {
    document.querySelectorAll('[data-lightbox]').forEach(el => {
        el.addEventListener('click', () => {
            document.getElementById('lightboxImg').src = el.dataset.src;
            document.getElementById('lightboxCap').textContent = el.dataset.caption || '';
            lightbox.classList.add('show');
        });
    });
    lightbox.addEventListener('click', () => lightbox.classList.remove('show'));
}

/* ---------- 纪念日表单 ---------- */
window.annivForm = function (id) {
    const el = document.getElementById('annivModal');
    if (!el) return;
    el.classList.add('show');
    if (id) {
        // edit mode handled inline by loading form page
    }
};
window.closeAnnivModal = function () {
    const el = document.getElementById('annivModal');
    if (el) el.classList.remove('show');
};

/* ---------- 日记心情选择器 ---------- */
window.pickMood = function (el, mood) {
    document.querySelectorAll('.mood-picker span').forEach(s => s.classList.remove('sel'));
    el.classList.add('sel');
    const input = document.getElementById('moodInput');
    if (input) input.value = mood;
};

/* ---------- 背景爱心粒子动画 ---------- */
(function heartsAnim() {
    const canvas = document.getElementById('hearts');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let W, H, hearts = [];

    function resize() {
        W = canvas.width = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function spawn() {
        return {
            x: Math.random() * W,
            y: H + 20,
            size: 8 + Math.random() * 14,
            speed: 0.4 + Math.random() * 0.9,
            drift: (Math.random() - 0.5) * 0.6,
            alpha: 0.15 + Math.random() * 0.25,
            rot: Math.random() * Math.PI
        };
    }

    for (let i = 0; i < 14; i++) {
        const h = spawn();
        h.y = Math.random() * H;
        hearts.push(h);
    }

    function drawHeart(x, y, s, a, rot) {
        ctx.save();
        ctx.translate(x, y);
        ctx.rotate(rot);
        ctx.globalAlpha = a;
        ctx.fillStyle = document.body.classList.contains('dark') ? '#FF8FA3' : '#FF6B81';
        ctx.beginPath();
        ctx.moveTo(0, s * 0.3);
        ctx.bezierCurveTo(0, 0, -s, 0, -s, s * 0.3);
        ctx.bezierCurveTo(-s, s * 0.7, 0, s * 0.9, 0, s * 1.2);
        ctx.bezierCurveTo(0, s * 0.9, s, s * 0.7, s, s * 0.3);
        ctx.bezierCurveTo(s, 0, 0, 0, 0, s * 0.3);
        ctx.fill();
        ctx.restore();
    }

    function loop() {
        ctx.clearRect(0, 0, W, H);
        hearts.forEach((h, i) => {
            h.y -= h.speed;
            h.x += h.drift;
            h.rot += 0.005;
            if (h.y < -30) {
                hearts[i] = spawn();
            }
            drawHeart(h.x, h.y, h.size, h.alpha, h.rot);
        });
        requestAnimationFrame(loop);
    }
    loop();
})();

/* ---------- 确认删除 ---------- */
window.confirmDelete = function (message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
};

/* ---------- 照片上传自动提交 ---------- */
const photoInput = document.getElementById('photoInput');
if (photoInput) {
    photoInput.addEventListener('change', () => {
        document.getElementById('photoForm').submit();
    });
}
