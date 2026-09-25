<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | XYZ Transport</title>
<style>
*{box-sizing:border-box}
html,body{min-height:100%;margin:0}
body{font-family:Arial,Helvetica,sans-serif;background:#f3f6f4;color:#132019}
.page{min-height:100vh;display:flex}

/* Left image section */
.brand{
    width:58%;
    min-height:100vh;
    background:#dbeafe;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.brand img{
    display:block;
    width:100%;
    height:100vh;
    object-fit:contain;
    object-position:center;
}

/* Existing login area */
.panel{
    width:42%;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:35px;
    background:#f3f6f4;
}
.box{width:100%;max-width:410px}
.box h2{font-size:30px;margin:0}
.sub{color:#6b7280;margin:8px 0 28px}
.field{margin:0 0 17px}
.field label{display:block;font-size:12px;font-weight:800;margin-bottom:6px}
.field input{
    width:100%;
    height:46px;
    border:1px solid #aab6af;
    padding:0 12px;
    outline:none;
    background:#fff
}
.field input:focus{
    border-color:#0b7a5d;
    box-shadow:0 0 0 3px rgba(11,122,93,.13)
}
.remember{
    font-size:13px;
    color:#526058;
    display:flex;
    align-items:center;
    gap:7px;
    margin:0 0 20px
}
.btn{
    width:100%;
    height:46px;
    border:0;
    background:#08765b;
    color:#fff;
    font-weight:900;
    cursor:pointer
}
.error{
    background:#fff0f0;
    border:1px solid #f2aaaa;
    color:#9f1f1f;
    padding:10px 12px;
    margin-bottom:16px;
    font-size:13px;
    font-weight:700
}
.note{
    font-size:12px;
    color:#7b857e;
    text-align:center;
    margin-top:16px
}

@media(max-width:900px){
    .brand{display:none}
    .panel{width:100%;padding:24px}
}
</style>
</head>
<body>
<div class="page">
    <section class="brand" aria-label="Bombay Goods Transport">
        <img src="{{ asset('images/bgt-login-poster.jpeg') }}" alt="Bombay Goods Transport - Every Trip Counts">
    </section>

    <main class="panel">
        <div class="box">
            <h2>Sign in</h2>
            <p class="sub">Use your administrator account to continue.</p>

            @if($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="field">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email','admin@gmail.com') }}" autofocus required autocomplete="username">
                </div>

                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required autocomplete="current-password">
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1"> Keep me signed in
                </label>

                <button class="btn" type="submit">Sign In</button>
            </form>

            <div class="note">Default login: admin@gmail.com / 123456</div>
        </div>
    </main>
</div>
</body>
</html>
