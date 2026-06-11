<?php
include "../config/database.php";

$posters = [];
$result  = mysqli_query($koneksi, "SELECT image FROM competitions WHERE image IS NOT NULL AND image != '' AND payment_status = 'paid' AND approval_status = 'approved' AND submission_status = 'published'");
while ($row = mysqli_fetch_assoc($result)) {
    $posters[] = $row['image'];
}

$has_posters = !empty($posters);

if ($has_posters) {
    while (count($posters) < 70) {
        $posters = array_merge($posters, $posters);
    }
    $cols = [[], [], [], [], [], [], []];
    foreach ($posters as $i => $img) {
        $cols[$i % 7][] = $img;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login atau daftar ke Pasti Info — platform informasi lomba dan kompetisi terlengkap untuk mahasiswa dan pelajar.">
    <title>Login & Register — Pasti Info</title>
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= filemtime(__DIR__ . '/../assets/css/global.css') ?>">
    <link rel="stylesheet" href="../assets/css/auth.css?v=<?= filemtime(__DIR__ . '/../assets/css/auth.css') ?>">
</head>
<body<?php if ($has_posters) echo ' class="has-posters"'; ?>>

<!-- ── ANIMATED POSTER BACKGROUND ── -->
<?php if ($has_posters): ?>
<div class="bg-columns">
    <?php foreach ($cols as $colPosters): ?>
    <div class="bg-col">
        <div class="col-track">
            <?php
            $doubled = array_merge($colPosters, $colPosters);
            foreach ($doubled as $img): ?>
                <img class="bg-poster"
                     src="../assets/images/<?php echo htmlspecialchars($img); ?>"
                     alt=""
                     onerror="this.style.display='none';">
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<section>
    <div class="login-box">

        <!-- LOGO -->
        <div class="logo-wrap">
            <img src="../assets/images/logo.svg" alt="Logo P Info">
        </div>

        <!-- ALERT -->
        <div id="alert-container" class="alert-container hidden animate-pulse">
            <!-- Messages go here via JS -->
        </div>

        <!-- TOGGLE LOGIN/REGISTER -->
        <div class="toggle-group">
            <button id="btnLogin" class="toggle-btn active" type="button" onclick="showLogin()">Login</button>
            <button id="btnRegister" class="toggle-btn" type="button" onclick="showRegister()">Register</button>
        </div>

        <!-- LOGIN FORM -->
        <form id="loginForm" action="../controllers/login.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Enter username..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Enter password..." required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>

        <!-- REGISTER FORM (Default: Hidden) -->
        <form id="registerForm" action="../controllers/register.php" method="POST" style="display:none;">
            <div class="form-group">
                <label>Username</label>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Enter username..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Enter email..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Enter password..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-box">
                    <input type="password" name="confirm_password" placeholder="Repeat password..." required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Register</button>
        </form>

        <div class="glow-line"></div> <!-- "or" divider style -->

        <div class="google-btn-container" style="display: flex; justify-content: center; width: 100%;">
            <div id="google-signin-btn"></div>
        </div>

    </div>
</section>

<!-- SDK Google & Script Handler -->
<script src="https://accounts.google.com/gsi/client" async defer onload="initGoogleSignIn()"></script>
<script>
function initGoogleSignIn() {
    google.accounts.id.initialize({
        client_id: "171421878386-imt8jhr76mglv6dkb9ibrijst01dndn1.apps.googleusercontent.com",
        callback: handleCredentialResponse,
        context: "signin",
        ux_mode: "popup",
        auto_prompt: false
    });
    
    // Calculate optimal width based on container width
    const container = document.querySelector('.google-btn-container');
    const width = container ? Math.max(200, Math.min(container.clientWidth, 400)) : 300;
    
    google.accounts.id.renderButton(
        document.getElementById("google-signin-btn"),
        { 
            type: "standard",
            shape: "pill",
            theme: "outline",
            text: "signin_with",
            size: "large",
            logo_alignment: "left",
            width: width
        }
    );
}

function handleCredentialResponse(response) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../controllers/google-auth.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'credential';
    input.value = response.credential;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function showLogin() {
    const login    = document.getElementById('loginForm');
    const register = document.getElementById('registerForm');

    register.style.display = 'none';
    login.style.display    = 'block';
    login.classList.remove('form-slide', 'form-slide-left');
    void login.offsetWidth;
    login.classList.add('form-slide-left');

    document.getElementById('btnLogin').classList.add('active');
    document.getElementById('btnRegister').classList.remove('active');
}

function showRegister() {
    const login    = document.getElementById('loginForm');
    const register = document.getElementById('registerForm');

    login.style.display    = 'none';
    register.style.display = 'block';
    register.classList.remove('form-slide', 'form-slide-left');
    void register.offsetWidth;
    register.classList.add('form-slide');

    document.getElementById('btnRegister').classList.add('active');
    document.getElementById('btnLogin').classList.remove('active');
}

window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    const alertBox = document.getElementById('alert-container');

    const success = urlParams.get('success');
    const error = urlParams.get('error');
    const tab = urlParams.get('tab');

    if (tab === 'register') {
        showRegister();
    } else {
        showLogin();
    }

    if (success === '1' || error) {
        alertBox.classList.remove('hidden');
        
        if (success === '1') {
            alertBox.innerText = "✅ Registration successful! Please login.";
            alertBox.className = "alert-container alert-success";
        } else if (error) {
            alertBox.innerText = "⚠️ " + decodeURIComponent(error);
            alertBox.className = "alert-container alert-error";
        }

        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 5000);
    }
}
</script>

</body>
</html>