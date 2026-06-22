<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BiteGo. | Page Unavailable</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      background: #fff;
      color: #111;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── NAV ── */
    nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 48px;
      height: 70px;
      border-bottom: 1px solid #e8e8e8;
      background: #fff;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 900;
      letter-spacing: -0.5px;
      color: #111;
      text-decoration: none;
    }

    .nav-btns {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .btn {
      padding: 10px 18px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      cursor: pointer;
      text-decoration: none;
      border: 2px solid #111;
      transition: background 0.2s, color 0.2s;
    }

    .btn-dark  { background: #111; color: #fff; }
    .btn-dark:hover  { background: #333; }
    .btn-light { background: #fff; color: #111; }
    .btn-light:hover { background: #f0f0f0; }

    /* ── MAIN CONTENT ── */
    main {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px 24px;
      background: #fff;
    }

    .card {
      text-align: center;
      max-width: 560px;
      width: 100%;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #111;
      color: #fff;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 7px 16px;
      border-radius: 999px;
      margin-bottom: 36px;
    }

    .badge::before {
      content: '';
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #f5c518;
      display: inline-block;
    }

    h1 {
      font-size: clamp(3rem, 8vw, 5.5rem);
      font-weight: 900;
      line-height: 1;
      letter-spacing: -2px;
      color: #111;
      margin-bottom: 20px;
    }

    h1 span {
      display: block;
      color: #777;
    }

    .sub {
      font-size: 1rem;
      color: #555;
      line-height: 1.6;
      margin-bottom: 44px;
      max-width: 420px;
      margin-left: auto;
      margin-right: auto;
    }

    .actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .actions .btn {
      padding: 13px 28px;
      font-size: 0.78rem;
    }

    /* ── DIVIDER ── */
    .divider {
      width: 60px;
      height: 3px;
      background: #111;
      border-radius: 2px;
      margin: 40px auto;
    }

    .hint {
      font-size: 0.8rem;
      color: #999;
      letter-spacing: 0.02em;
    }

    /* ── FOOTER ── */
    footer {
      text-align: center;
      padding: 24px;
      font-size: 0.75rem;
      color: #aaa;
      border-top: 1px solid #e8e8e8;
      letter-spacing: 0.03em;
    }

    footer strong { color: #111; }
  </style>
</head>
<body>

  <!-- NAV -->
  <nav>
    <a class="logo" href="frontpage.php">BiteGo.</a>
    <div class="nav-btns">
      <a href="apply_vendor.php" class="btn btn-dark">Apply for Vendor</a>
      <a href="loginforcust.php" class="btn btn-dark">Customer Login</a>
      <a href="login.php" class="btn btn-dark">Vendor / Admin</a>
      <a href="vendorSelectPage.php" class="btn btn-dark">Browse Vendor</a>
    </div>
  </nav>

  <!-- MAIN -->
  <main>
    <div class="card">
      <div class="badge">Under Construction</div>

      <h1>
        Coming
        <span>Soon.</span>
      </h1>

      <p class="sub">
        This page is currently unavailable. We're still cooking things up —
        check back shortly or head back to the homepage.
      </p>

      <div class="actions">
        <a href="frontpage.php" class="btn btn-dark">← Back to Home</a>
        <a href="mailto:support@bitego.com" class="btn btn-light">Contact Support</a>
      </div>

      <div class="divider"></div>
      <p class="hint">Need help? Reach us at <strong>support@bitego.com</strong></p>
    </div>
  </main>

  <!-- FOOTER -->
  <footer>
    &copy; 2025 <strong>BiteGo.</strong> — Skip the Line, Dine Online.
  </footer>

</body>
</html>