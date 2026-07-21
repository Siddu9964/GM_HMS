<?php
session_start();
if (isset($_GET['branch'])) {
    $_SESSION['hospital_branch'] = $_GET['branch'];
}
require_once 'includes/db.php';
if (isset($_SESSION['vendor_id'])) { 
    if (isset($_GET['indent_no'])) {
        echo "<script>localStorage.setItem('pending_indent', '{$_GET['indent_no']}'); window.location.href='index.php';</script>";
        exit();
    }
    header("Location: index.php"); 
    exit(); 
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Credentials required']); exit();
    }
    $vendor = $db->fetchOne(
        "SELECT supplier_id, supplier_name, company_name, password FROM ph_suppliers
         WHERE (email = ? OR phone = ? OR supplier_id = ?) AND status = 'active'",
        [$username, $username, $username]
    );
    if ($vendor && $password === $vendor['password']) {
        $_SESSION['vendor_id']   = $vendor['supplier_id'];
        $_SESSION['vendor_name'] = $vendor['company_name'] ?: $vendor['supplier_name'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials. Please try again.']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vendor Portal Login | MediVend Nexus</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #06b6d4;
      --primary-light: #06b6d4;
      --accent: #22d3ee;
      --success: #10b981;
      --danger: #ef4444;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

    body {
      min-height: 100vh;
      background: linear-gradient(135deg, #e8eeff 0%, #f0f4ff 40%, #e0f7fa 100%);
      background-attachment: fixed;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    /* ── Animated background orbs ── */
    .orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(70px);
      pointer-events: none;
      animation: orbFloat 10s ease-in-out infinite alternate;
    }
    .orb-1 { width: 550px; height: 550px; background: radial-gradient(circle, rgba(6,182,212,0.16), transparent 70%); top: -160px; left: -160px; }
    .orb-2 { width: 450px; height: 450px; background: radial-gradient(circle, rgba(34,211,238,0.13), transparent 70%); bottom: -120px; right: -120px; animation-delay: -5s; }
    .orb-3 { width: 350px; height: 350px; background: radial-gradient(circle, rgba(16,185,129,0.09), transparent 70%); top: 35%; left: 55%; animation-delay: -3s; }
    .orb-4 { width: 280px; height: 280px; background: radial-gradient(circle, rgba(6,182,212,0.10), transparent 70%); top: 20%; right: 5%; animation-delay: -7s; animation-duration: 14s; }
    @keyframes orbFloat {
      0%   { transform: translate(0,0) scale(1); }
      33%  { transform: translate(20px,-15px) scale(1.04); }
      66%  { transform: translate(-10px,25px) scale(0.97); }
      100% { transform: translate(30px,15px) scale(1.06); }
    }

    /* ── Bubble container ── */
    .bubbles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }

    /* ── Large hollow bubble ── */
    .bubble {
      position: absolute;
      bottom: -200px;
      border-radius: 50%;
      pointer-events: none;
      animation: bubbleRise linear infinite;
      opacity: 0;
    }
    /* Type A — outlined glass bubble */
    .bubble.glass {
      background: radial-gradient(circle at 35% 30%, rgba(255,255,255,0.55), rgba(255,255,255,0.08) 60%, transparent);
      border: 1.5px solid rgba(6,182,212,0.25);
      box-shadow: inset 0 0 12px rgba(255,255,255,0.3), 0 0 8px rgba(6,182,212,0.10);
    }
    /* Type B — soft filled bubble */
    .bubble.soft {
      background: radial-gradient(circle at 40% 35%, rgba(6,182,212,0.18), rgba(34,211,238,0.06) 70%, transparent);
      border: 1px solid rgba(6,182,212,0.18);
    }
    /* Type C — tiny sparkle dot */
    .bubble.dot {
      background: rgba(6,182,212,0.30);
      box-shadow: 0 0 6px rgba(6,182,212,0.35);
    }
    /* Type D — ring only */
    .bubble.ring {
      background: transparent;
      border: 2px solid rgba(6,182,212,0.22);
      box-shadow: 0 0 10px rgba(6,182,212,0.10);
    }

    @keyframes bubbleRise {
      0%   { transform: translateY(0) translateX(0) scale(1);   opacity: 0; }
      5%   { opacity: 1; }
      50%  { transform: translateY(-50vh) translateX(var(--drift)) scale(var(--mid-scale)); }
      90%  { opacity: 0.7; }
      100% { transform: translateY(-110vh) translateX(calc(var(--drift) * 1.6)) scale(0.8); opacity: 0; }
    }

    /* ── Horizontal wave bubbles ── */
    .wave-bubble {
      position: absolute;
      border-radius: 50%;
      background: radial-gradient(circle at 40% 35%, rgba(255,255,255,0.45), rgba(6,182,212,0.08));
      border: 1px solid rgba(6,182,212,0.18);
      animation: waveDrift ease-in-out infinite alternate;
      opacity: 0.55;
      pointer-events: none;
    }
    @keyframes waveDrift {
      0%   { transform: translate(0, 0) scale(1); }
      50%  { transform: translate(var(--wx), var(--wy)) scale(var(--ws)); }
      100% { transform: translate(calc(var(--wx)*-0.6), calc(var(--wy)*0.4)) scale(1.05); }
    }

    /* ── Shimmer highlight on glass bubbles ── */
    .bubble.glass::after {
      content: '';
      position: absolute;
      top: 15%; left: 20%;
      width: 30%; height: 20%;
      background: rgba(255,255,255,0.55);
      border-radius: 50%;
      filter: blur(4px);
      animation: shimmerGlow 3s ease-in-out infinite alternate;
    }
    @keyframes shimmerGlow {
      0%   { opacity: 0.5; transform: scale(1); }
      100% { opacity: 1;   transform: scale(1.15); }
    }

    /* Card */
    .login-wrapper {
      width: 100%;
      max-width: 460px;
      padding: 20px;
      position: relative;
      z-index: 10;
      animation: cardEntry 0.7s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    @keyframes cardEntry {
      from { opacity: 0; transform: translateY(40px) scale(0.95); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .login-card {
      background: rgba(255,255,255,0.78);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(255,255,255,0.9);
      border-radius: 28px;
      padding: 48px 44px;
      box-shadow: 0 24px 60px rgba(6,182,212,0.12), 0 4px 16px rgba(0,0,0,0.05);
      position: relative;
      overflow: hidden;
    }
    .login-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--accent), var(--success));
      animation: gradientShift 4s ease infinite;
      background-size: 200%;
    }
    @keyframes gradientShift {
      0%,100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    /* Logo */
    .login-logo {
      width: 68px; height: 68px;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      border-radius: 20px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 1.6rem;
      margin: 0 auto 24px;
      box-shadow: 0 12px 28px rgba(6,182,212,0.25);
      position: relative;
      animation: logoPulse 3s ease-in-out infinite;
    }
    @keyframes logoPulse {
      0%,100% { box-shadow: 0 12px 28px rgba(6,182,212,0.25); }
      50% { box-shadow: 0 16px 40px rgba(6,182,212,0.38); }
    }
    .login-logo::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 24px;
      border: 2px solid rgba(6,182,212,0.15);
      animation: ringPulse 2s ease-in-out infinite;
    }
    @keyframes ringPulse {
      0%,100% { transform: scale(1); opacity: 0.8; }
      50% { transform: scale(1.06); opacity: 0.3; }
    }

    .login-title {
      font-size: 1.75rem; font-weight: 800; text-align: center;
      background: linear-gradient(135deg, var(--primary), #06b6d4);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
      letter-spacing: -0.8px; margin-bottom: 6px;
    }
    .login-subtitle {
      text-align: center; color: #9ca3af; font-size: 0.88rem;
      font-weight: 500; margin-bottom: 36px;
    }

    /* Form */
    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block; font-size: 0.78rem; font-weight: 700;
      color: #4b5563; margin-bottom: 7px;
      display: flex; align-items: center; gap: 6px;
    }
    .form-label i { color: var(--primary); font-size: 0.75rem; }

    .input-wrap { position: relative; }
    .form-control {
      width: 100%;
      background: rgba(248,250,255,0.8);
      border: 1.5px solid rgba(6,182,212,0.1);
      border-radius: 12px;
      padding: 13px 16px 13px 44px;
      color: #0f172a; font-size: 0.92rem; font-weight: 500;
      transition: all 0.3s;
    }
    .form-control::placeholder { color: #c4c9d4; font-weight: 400; }
    .form-control:focus {
      outline: none; border-color: var(--primary);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(6,182,212,0.08);
    }
    .input-icon {
      position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
      color: #9ca3af; font-size: 0.9rem; transition: 0.3s;
      pointer-events: none;
    }
    .form-control:focus + .input-icon, .input-wrap:focus-within .input-icon { color: var(--primary); }
    .toggle-pwd {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      color: #9ca3af; cursor: pointer; transition: 0.2s; padding: 4px;
    }
    .toggle-pwd:hover { color: var(--primary); }

    .forgot-link {
      text-align: right; margin-bottom: 22px;
      font-size: 0.8rem;
    }
    .forgot-link a {
      color: var(--primary); text-decoration: none; font-weight: 600;
      transition: 0.2s;
    }
    .forgot-link a:hover { text-decoration: underline; }

    /* Button */
    .login-btn {
      width: 100%;
      background: linear-gradient(135deg, var(--primary), var(--primary-light));
      color: #fff; border: none;
      border-radius: 12px; padding: 14px;
      font-size: 0.95rem; font-weight: 700;
      cursor: pointer; transition: all 0.3s;
      position: relative; overflow: hidden;
      box-shadow: 0 6px 20px rgba(6,182,212,0.25);
      display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .login-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(6,182,212,0.35); }
    .login-btn:active { transform: translateY(0); }
    .login-btn::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.12), transparent);
    }
    .btn-spinner { display: none; }
    .login-btn.loading .btn-text { display: none; }
    .login-btn.loading .btn-spinner { display: block; }

    /* Divider */
    .login-divider {
      display: flex; align-items: center; gap: 12px;
      color: #d1d5db; font-size: 0.75rem; margin: 22px 0;
    }
    .login-divider::before, .login-divider::after {
      content: ''; flex: 1; height: 1px; background: rgba(0,0,0,0.07);
    }

    /* Trust badges */
    .trust-row {
      display: flex; justify-content: center; gap: 20px; margin-top: 22px;
    }
    .trust-item {
      display: flex; align-items: center; gap: 5px;
      font-size: 0.7rem; font-weight: 600; color: #9ca3af;
    }
    .trust-item i { color: var(--success); }

    /* Indent badge */
    .indent-notice {
      background: rgba(6,182,212,0.06);
      border: 1px solid rgba(6,182,212,0.12);
      border-radius: 12px;
      padding: 11px 14px;
      margin-bottom: 20px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--primary);
      display: flex; align-items: center; gap: 10px;
    }
    .indent-notice i { opacity: 0.7; }

    /* Error shake */
    @keyframes shake {
      0%,100%{transform:translateX(0);}
      20%{transform:translateX(-6px);}
      40%{transform:translateX(6px);}
      60%{transform:translateX(-4px);}
      80%{transform:translateX(4px);}
    }
    .shake { animation: shake 0.4s ease; }

    .error-msg {
      color: var(--danger); font-size: 0.78rem; font-weight: 600;
      margin-top: 6px; display: none; align-items: center; gap: 5px;
    }
    .error-msg.show { display: flex; }
  </style>
</head>
<body>

<!-- Background -->
<div class="orb orb-1"></div>
<div class="orb orb-2"></div>
<div class="orb orb-3"></div>
<div class="orb orb-4"></div>
<div class="bubbles" id="bubblesContainer"></div>

<div class="login-wrapper">
  <div class="login-card">

    <div class="login-logo"><i class="fas fa-truck-fast"></i></div>
    <h1 class="login-title">Vendor Portal</h1>
    <p class="login-subtitle">Secure access to procurement & quotations</p>

    <?php if(isset($_GET['indent_no'])): ?>
    <div class="indent-notice">
      <i class="fas fa-tag"></i>
      Responding to indent: <strong><?= htmlspecialchars($_GET['indent_no']) ?></strong>
    </div>
    <script>localStorage.setItem('pending_indent','<?= htmlspecialchars($_GET['indent_no']) ?>');</script>
    <?php endif; ?>

    <form id="loginForm" onsubmit="handleLogin(event)" novalidate>
      <div class="form-group">
        <label class="form-label"><i class="fas fa-id-card"></i> Vendor ID or Email</label>
        <div class="input-wrap">
          <input type="text" id="username" class="form-control" placeholder="Enter your ID or email" autocomplete="username" required>
          <i class="fas fa-user input-icon"></i>
        </div>
        <div class="error-msg" id="err-username"><i class="fas fa-exclamation-circle"></i> <span></span></div>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
        <div class="input-wrap">
          <input type="password" id="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required style="padding-right:42px;">
          <i class="fas fa-lock input-icon"></i>
          <i class="fas fa-eye toggle-pwd" id="togglePwd"></i>
        </div>
        <div class="error-msg" id="err-password"><i class="fas fa-exclamation-circle"></i> <span></span></div>
      </div>

      <div class="forgot-link"><a href="#" onclick="showResetModal(event)">Forgot Password?</a></div>

      <button type="submit" class="login-btn" id="loginBtn">
        <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Sign In to Portal</span>
        <span class="btn-spinner"><i class="fas fa-spinner fa-spin"></i> Authenticating…</span>
      </button>

      <div id="globalError" class="error-msg" style="margin-top:12px;justify-content:center;">
        <i class="fas fa-exclamation-circle"></i> <span></span>
      </div>
    </form>

    <div class="login-divider">Secured by MediVend Nexus</div>

    <div class="trust-row">
      <div class="trust-item"><i class="fas fa-shield-alt"></i> Encrypted</div>
      <div class="trust-item"><i class="fas fa-lock"></i> Private</div>
      <div class="trust-item"><i class="fas fa-check-circle"></i> Verified</div>
    </div>
  </div>
</div>

<script>
// ── Bubble System ──
(function() {
    const c = document.getElementById('bubblesContainer');
    const types = ['glass','glass','glass','soft','soft','dot','dot','ring','ring'];

    // Rising bubbles — 45 of them
    for (let i = 0; i < 45; i++) {
        const b = document.createElement('div');
        const type = types[Math.floor(Math.random() * types.length)];
        b.className = `bubble ${type}`;

        const size = type === 'dot'
            ? (4 + Math.random() * 8)         // dots: 4–12px
            : type === 'ring'
            ? (30 + Math.random() * 60)        // rings: 30–90px
            : type === 'soft'
            ? (20 + Math.random() * 50)        // soft: 20–70px
            : (40 + Math.random() * 100);      // glass: 40–140px

        const drift = (Math.random() - 0.5) * 160;
        const midScale = 0.9 + Math.random() * 0.3;
        const dur = 8 + Math.random() * 20;
        const delay = -(Math.random() * 30);

        b.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${Math.random() * 105}vw;
            animation-duration: ${dur}s;
            animation-delay: ${delay}s;
            --drift: ${drift}px;
            --mid-scale: ${midScale};
        `;
        c.appendChild(b);
    }

    // Wave bubbles — 12 floating in place
    const waveColors = [
        'rgba(6,182,212,0.12)','rgba(34,211,238,0.10)',
        'rgba(16,185,129,0.09)','rgba(255,255,255,0.25)'
    ];
    for (let i = 0; i < 12; i++) {
        const w = document.createElement('div');
        w.className = 'wave-bubble';
        const wSize = 30 + Math.random() * 90;
        const wx = (Math.random() - 0.5) * 80;
        const wy = (Math.random() - 0.5) * 50;
        const ws = 0.88 + Math.random() * 0.28;
        const wDur = 5 + Math.random() * 10;
        const col = waveColors[Math.floor(Math.random() * waveColors.length)];
        w.style.cssText = `
            width: ${wSize}px;
            height: ${wSize}px;
            left: ${Math.random() * 95}vw;
            top: ${Math.random() * 95}vh;
            background: radial-gradient(circle at 40% 35%, rgba(255,255,255,0.45), ${col});
            animation-duration: ${wDur}s;
            animation-delay: ${-(Math.random() * wDur)}s;
            --wx: ${wx}px;
            --wy: ${wy}px;
            --ws: ${ws};
        `;
        c.appendChild(w);
    }
})();

// ── Toggle Password ──
document.getElementById('togglePwd').addEventListener('click', function() {
    const p = document.getElementById('password');
    const isText = p.type === 'text';
    p.type = isText ? 'password' : 'text';
    this.className = `fas fa-${isText ? 'eye' : 'eye-slash'} toggle-pwd`;
});

// ── Login ──
async function handleLogin(e) {
    e.preventDefault();
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const btn = document.getElementById('loginBtn');
    const gErr = document.getElementById('globalError');

    // Reset errors
    gErr.classList.remove('show');

    if (!username) { showFieldError('username', 'Please enter your Vendor ID or email'); return; }
    if (!password) { showFieldError('password', 'Please enter your password'); return; }

    btn.classList.add('loading');
    btn.disabled = true;

    try {
        const res = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const result = await res.json();
        if (result.success) {
            btn.innerHTML = '<i class="fas fa-check-circle"></i> <span class="btn-text">Authenticated!</span>';
            btn.style.background = 'linear-gradient(135deg,#10b981,#34d399)';
            const params = new URLSearchParams(window.location.search);
            if (params.get('indent_no')) localStorage.setItem('pending_indent', params.get('indent_no'));
            setTimeout(() => location.href = 'index.php', 700);
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
            gErr.querySelector('span').textContent = result.message || 'Invalid credentials';
            gErr.classList.add('show');
            document.querySelector('.login-card').classList.add('shake');
            setTimeout(() => document.querySelector('.login-card').classList.remove('shake'), 500);
        }
    } catch (err) {
        btn.classList.remove('loading');
        btn.disabled = false;
        gErr.querySelector('span').textContent = 'Connection failed. Please try again.';
        gErr.classList.add('show');
    }
}

function showFieldError(field, msg) {
    const el = document.getElementById(`err-${field}`);
    if (el) { el.querySelector('span').textContent = msg; el.classList.add('show'); }
    document.getElementById(field)?.classList.add('shake');
    setTimeout(() => { el?.classList.remove('show'); document.getElementById(field)?.classList.remove('shake'); }, 3000);
}

function showResetModal(e) {
    e.preventDefault();
    Swal.fire({
        title: '<span style="font-family:Inter;font-weight:800;">Reset Password</span>',
        html: '<p style="font-family:Inter;color:#6b7280;font-size:0.88rem;">Enter your registered Email or Phone Number.</p>',
        input: 'email',
        inputPlaceholder: 'your@email.com',
        showCancelButton: true,
        confirmButtonText: 'Send Reset Link',
        confirmButtonColor: '#06b6d4',
        inputAttributes: { style: 'font-family:Inter;border-radius:10px;' },
        showLoaderOnConfirm: true,
        preConfirm: (v) => {
            if (!v) return Swal.showValidationMessage('Email is required');
            return new Promise(r => setTimeout(r, 1200));
        }
    }).then(r => {
        if (r.isConfirmed) Swal.fire({ icon: 'success', title: 'Sent!', text: 'If this email exists, a reset link will be sent.', confirmButtonColor: '#06b6d4' });
    });
}
</script>
</body>
</html>
