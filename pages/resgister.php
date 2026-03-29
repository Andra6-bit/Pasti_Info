<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - P Info</title>
    <link rel="stylesheet" href="../Assets/css/login.css">
    <style>
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; text-align: center; }
        .alert-error   { background: rgba(229,62,62,0.18); color: #fca5a5; border: 1px solid rgba(229,62,62,0.3); }
        .alert-success { background: rgba(34,197,94,0.18); color: #86efac; border: 1px solid rgba(34,197,94,0.3); }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); }
        .input-box { margin-bottom: 0; }
    </style>
</head>
<body>
    <section>
        <div class="Login-box">
            <img src="../Assets/images/rmbg_logo.png" class="logo" alt="Logo P Info">
            <h2>Daftar Akun</h2>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success">Registrasi berhasil! <a href="login.php" style="color:#86efac;font-weight:700;">Login sekarang</a></div>
            <?php endif; ?>

            <form action="register_proses.php" method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-box">
                        <span class="icon"><ion-icon name="mail"></ion-icon></span>
                        <input type="email" name="email" placeholder="Masukkan email..." required
                               value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-box">
                        <span class="icon"><ion-icon name="lock-closed"></ion-icon></span>
                        <input type="password" name="password" placeholder="Masukkan password..." required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-box">
                        <span class="icon"><ion-icon name="checkmark-circle"></ion-icon></span>
                        <input type="password" name="confirm_password" placeholder="Ulangi password..." required>
                    </div>
                </div>
                <button type="submit">Daftar Sekarang</button>
            </form>

            <div class="register-link">
                <p>Sudah punya akun? <a href="login.php">Login</a></p>
            </div>
        </div>
    </section>
    <script src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons.js"></script>
</body>
</html>