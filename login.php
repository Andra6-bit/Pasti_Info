<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - P Info</title>
    <link rel="stylesheet" href="login.css" />
  </head>

  <body>
    <section>
      <div class="Login-box">
        <form action="login_proses.php" method="POST">
          <img src="Assets/rmbg_logo.png" class="logo" alt="Logo P Info" />
          <h2>Login</h2>
          <table class="form-table">
            <tr>
              <td><label>Email</label></td>
              <td>
                <div class="input-box">
                  <span class="icon"><ion-icon name="mail"></ion-icon></span>
                  <input type="email" name="email" placeholder="Enter Email..." required />
                </div>
              </td>
            </tr>
            <tr>
              <td><label>Password</label></td>
              <td>
                <div class="input-box">
                  <span class="icon"><ion-icon name="eye"></ion-icon></span>
                  <input type="password" name="password" placeholder="Enter Password..." required />
                </div>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <button type="submit">Login</button>
              </td>
            </tr>
          </table>
          <div class="register-link">
            <!-- ✅ FIX: Link diarahkan ke register.php -->
            <p>Didn't Have Account? <a href="register.php">Register</a></p>
          </div>
        </form>
      </div>
    </section>

    <script src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons.js"></script>
  </body>
</html>