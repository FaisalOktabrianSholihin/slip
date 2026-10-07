<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Akses Ditolak - E-Slip Gaji Mitratani</title>
  <link rel="icon" href="{{ asset('assets/logo.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F4F7F5;font-family:'Plus Jakarta Sans',sans-serif;color:#1B2C3D;padding:24px;box-sizing:border-box}
    .box{max-width:440px;text-align:center;background:#fff;border:1px solid #E1E8E3;border-radius:18px;padding:36px 30px;box-shadow:0 20px 50px rgba(0,0,0,.06)}
    .icon{width:64px;height:64px;border-radius:50%;background:#FBEBE6;color:#B5482A;display:grid;place-items:center;font-size:30px;margin:0 auto 16px}
    h1{font-size:19px;margin:0 0 8px}
    p{font-size:14px;line-height:1.6;color:#5B6A61;margin:0 0 22px}
    a.btn{display:inline-flex;align-items:center;gap:8px;background:#078B4C;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:11px 22px;border-radius:10px}
    a.btn:hover{background:#006B3C}
  </style>
</head>
<body>
  <div class="box">
    <div class="icon"><i class="bi bi-shield-lock-fill"></i></div>
    <h1>Akses Ditolak</h1>
    <p>{{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses untuk membuka halaman ini. Hubungi administrator jika menurut Anda ini keliru.' }}</p>
    @auth
      <a class="btn" href="{{ route('dashboard') }}"><i class="bi bi-house-fill"></i> Kembali ke Dashboard</a>
    @else
      <a class="btn" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> Masuk</a>
    @endauth
  </div>
</body>
</html>
