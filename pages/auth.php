<?php
include "../config/koneksi.php";

$posters = [];
$result  = mysqli_query($koneksi, "SELECT image FROM competitions WHERE image IS NOT NULL AND image != ''");
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth - P Info</title>
    <link rel="stylesheet" href="../Assets/css/auth.css?v=<?php echo time(); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
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
                     src="../Assets/images/<?php echo htmlspecialchars($img); ?>"
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
            <img src="../Assets/images/logo.svg" alt="Logo P Info">
        </div>

        <!-- ALERT -->
        <div id="alert-container" class="hidden mb-4 p-4 rounded-xl text-sm font-medium animate-pulse border">
            <!-- Pesan akan masuk ke sini via JS -->
        </div>

        <!-- TOGGLE LOGIN/REGISTER -->
        <div class="toggle-group">
            <button id="btnLogin" class="toggle-btn active" type="button" onclick="showLogin()">Login</button>
            <button id="btnRegister" class="toggle-btn" type="button" onclick="showRegister()">Register</button>
        </div>

        <!-- FORM LOGIN -->
        <form id="loginForm" action="login_proses.php" method="POST">
            <div class="form-group">
                <label>Username</label>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Masukkan username..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Masukkan password..." required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <!-- FORM REGISTER (Default: Hidden) -->
        <form id="registerForm" action="register_proses.php" method="POST" style="display:none;">
            <div class="form-group">
                <label>Username</label>
                <div class="input-box">
                    <input type="text" name="username" placeholder="Masukkan username..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Masukkan email..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Masukkan password..." required>
                </div>
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="input-box">
                    <input type="password" name="confirm_password" placeholder="Ulangi password..." required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Daftar</button>
        </form>

        <!-- ── PEMISAH & GOOGLE LOGIN (SEKARANG DI BAWAH SEMUA FORM) ── -->
        <div class="glow-line"></div> <!-- CSS Tuan akan menampilkan teks "atau" di sini -->

        <div class="google-btn-container" style="display: flex; justify-content: center;">
            <div id="g_id_onload"
                data-client_id="171421878386-imt8jhr76mglv6dkb9ibrijst01dndn1.apps.googleusercontent.com" 
                data-context="signin"
                data-ux_mode="popup"
                data-callback="handleCredentialResponse"
                data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                data-type="standard"
                data-shape="pill" 
                data-theme="outline"
                data-text="signin_with"
                data-size="large"
                data-logo_alignment="left"
                data-width="348">
            </div>
        </div>

    </div>
</section>

<!-- SDK Google & Script Handler -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
function handleCredentialResponse(response) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'google_proses.php';
    
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

    // 1. Cek tab mana yang harus dibuka
    if (tab === 'register') {
        showRegister();
    } else {
        showLogin(); // Pastikan default ke login jika tidak ada instruksi tab
    }

    // 2. Tampilkan Alert Cantik
    if (success === '1' || error) {
        alertBox.classList.remove('hidden');
        
        if (success === '1') {
            alertBox.innerText = "Pendaftaran berhasil! Silakan login.";
            alertBox.className = "mb-4 p-4 rounded-xl text-sm font-medium border border-green-200 bg-green-50 text-green-700";
        } else if (error) {
            alertBox.innerText = decodeURIComponent(error);
            alertBox.className = "mb-4 p-4 rounded-xl text-sm font-medium border border-red-200 bg-red-50 text-red-700";
            
            // HAPUS showRegister() dari sini agar tidak memaksa pindah tab
        }

        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 5000);
    }
}
</script>

</body>
</html>