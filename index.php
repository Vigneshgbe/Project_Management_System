<?php
require_once 'config.php';
initSession();

// Already logged in
if (!empty($_SESSION['crm_user_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $db = getCRMDB();
        $stmt = $db->prepare("SELECT id,name,email,password,role,avatar,status FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $u = $stmt->get_result()->fetch_assoc();
        if ($u && $u['status'] === 'active' && password_verify($pass, $u['password'])) {
            $_SESSION['crm_user_id']    = $u['id'];
            $_SESSION['crm_name']       = $u['name'];
            $_SESSION['crm_email']      = $u['email'];
            $_SESSION['crm_role']       = $u['role'];
            $_SESSION['crm_avatar']     = $u['avatar'];
            $_SESSION['crm_last_activity'] = time();
            $db->query("UPDATE users SET last_login=NOW() WHERE id=".(int)$u['id']);
            logActivity('login', 'user', $u['id']);
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid credentials or account inactive.';
        }
    } else {
        $error = 'Please enter email and password.';
    }
}
$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Log In — Internal CRM</title>
<link rel="icon" type="image/x-icon" href="https://thepadak.com/index_assets/padak_p.png">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Bricolage+Grotesque:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0f1117;--bg2:#161b27;--bg3:#1e2538;--border:#2a3348;
  --text:#e8eaf0;--text2:#9aa3b8;--text3:#5a6478;
  --orange:#f97316;--red:#ef4444;--green:#10b981;
  --font:'Plus Jakarta Sans',sans-serif;--font-d:'Bricolage Grotesque',sans-serif
}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(var(--border) 1px,transparent 1px),linear-gradient(90deg,var(--border) 1px,transparent 1px);background-size:40px 40px;opacity:.3;pointer-events:none}
.bg-glow{position:fixed;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(249,115,22,.08) 0%,transparent 70%);top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none}
.card{
  position:relative;background:var(--bg2);border:1px solid var(--border);
  border-radius:20px;padding:40px;width:100%;max-width:420px;
  box-shadow:0 20px 60px rgba(0,0,0,.6)
}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:32px;justify-content:center}
.logo-mark{width:42px;height:42px;background:var(--orange);border-radius:10px;display:flex;align-items:center;justify-content:center;font-family:var(--font-d);font-weight:700;font-size:20px;color:#fff}
.logo-text{font-family:var(--font-d);font-weight:700;font-size:22px}
.logo-text span{color:var(--orange)}
h1{font-family:var(--font-d);font-size:24px;font-weight:700;text-align:center;margin-bottom:6px}
.sub{text-align:center;color:var(--text2);font-size:13.5px;margin-bottom:28px}
.form-group{margin-bottom:16px}
.form-label{display:block;font-size:12px;font-weight:600;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.form-control{
  width:100%;background:var(--bg3);border:1px solid var(--border);
  border-radius:8px;padding:11px 14px;color:var(--text);font-size:14px;
  font-family:var(--font);transition:border-color .15s
}
.form-control:focus{outline:none;border-color:var(--orange)}
.form-control::placeholder{color:var(--text3)}
.btn{
  width:100%;padding:12px;background:var(--orange);color:#fff;border:none;
  border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;
  font-family:var(--font-d);transition:opacity .15s,transform .1s;margin-top:6px
}
.btn:hover{opacity:.9}
.btn:active{transform:scale(.98)}
.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:10px 14px;color:var(--red);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.info{background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.2);border-radius:8px;padding:10px 14px;color:#818cf8;font-size:13px;margin-bottom:16px}
.hint{text-align:center;margin-top:20px;font-size:12px;color:var(--text3)}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>
<div class="card">
  <div class="logo">
    <div class="logo-mark">P</div>
    <div class="logo-text">Padak <span>CRM</span></div>
  </div>
  <h1>Welcome back</h1>
  <p class="sub">Internal project management system</p>

  <?php if ($timeout): ?>
  <div class="info">⏱ Session expired. Please sign in again.</div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="error">✕ <?= h($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" placeholder="you@thepadak.com" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" placeholder="Enter password" required>
    </div>
    <button type="submit" class="btn">Sign In →</button>
  </form>
  <p class="hint">Padak (Pvt) Ltd — Internal Use Only</p>
</div>
</body>
</html>
