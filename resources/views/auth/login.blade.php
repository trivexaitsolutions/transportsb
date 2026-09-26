<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login | BGT Transport</title>

<style>
:root{
    --navy:#052d73;
    --blue:#0756c8;
    --orange:#ff6b00;
    --text:#0a234f;
    --muted:#65748c;
    --border:#d8e0eb;
    --card:#ffffff;
}

*{box-sizing:border-box}

html,body{
    width:100%;
    min-height:100%;
    margin:0;
}

body{
    font-family:Arial,Helvetica,sans-serif;
    color:var(--text);
    background:#dceeff;
}

.login-page{
    position:relative;
    min-height:100vh;
    width:100%;
    display:flex;
    align-items:center;
    justify-content:flex-end;
    padding:40px clamp(38px,5vw,96px);
    overflow:hidden;
    background:
        linear-gradient(90deg,rgba(0,32,88,.03),rgba(255,255,255,.02)),
        url("{{ asset('images/bgt-login-background.png') }}") center center / cover no-repeat;
}

.login-card{
    width:min(500px,100%);
    background:rgba(255,255,255,.96);
    border:1px solid rgba(255,255,255,.85);
    border-radius:24px;
    box-shadow:0 28px 75px rgba(6,36,88,.24);
    padding:32px 38px 28px;
    backdrop-filter:blur(8px);
}

.logo-wrap{
    display:flex;
    justify-content:center;
    margin-bottom:20px;
}

.logo-wrap img{
    display:block;
    width:min(285px,72%);
    height:auto;
}

.login-title{
    margin:0;
    font-size:36px;
    line-height:1.05;
    font-weight:900;
    letter-spacing:-.7px;
    color:#071e48;
}

.login-subtitle{
    margin:8px 0 24px;
    color:var(--muted);
    font-size:16px;
    line-height:1.5;
}

.alert-error{
    margin-bottom:16px;
    padding:11px 13px;
    border:1px solid #fecaca;
    border-radius:9px;
    background:#fef2f2;
    color:#991b1b;
    font-size:13px;
    font-weight:700;
}

.field{
    margin-bottom:16px;
}

.field label{
    display:block;
    margin-bottom:7px;
    font-size:13px;
    font-weight:800;
    color:#0a1f45;
}

.input-shell{
    position:relative;
}

.input-shell input{
    width:100%;
    height:50px;
    border:1px solid var(--border);
    border-radius:9px;
    background:#fff;
    padding:0 46px 0 48px;
    outline:none;
    color:#132b50;
    font-size:15px;
    transition:.18s ease;
}

.input-shell input:focus{
    border-color:#4385db;
    box-shadow:0 0 0 3px rgba(43,117,209,.12);
}

.input-icon{
    position:absolute;
    left:16px;
    top:50%;
    width:19px;
    height:19px;
    transform:translateY(-50%);
    color:#18335f;
    pointer-events:none;
}

.eye-button{
    position:absolute;
    right:12px;
    top:50%;
    width:34px;
    height:34px;
    transform:translateY(-50%);
    border:0;
    background:transparent;
    color:#153360;
    display:grid;
    place-items:center;
    cursor:pointer;
    border-radius:7px;
}

.eye-button:hover,
.eye-button:focus{
    background:#edf4ff;
    outline:none;
}

.login-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin:4px 0 20px;
}

.remember{
    display:flex;
    align-items:center;
    gap:9px;
    color:#64748b;
    font-size:13px;
    cursor:pointer;
}

.remember input{
    width:17px;
    height:17px;
    accent-color:#0b59c7;
}

.forgot{
    color:#0864df;
    text-decoration:none;
    font-size:13px;
    font-weight:700;
}

.forgot:hover{text-decoration:underline}

.signin-btn{
    width:100%;
    height:50px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    border:0;
    border-radius:8px;
    background:linear-gradient(90deg,#085bbd,#083e9d);
    color:#fff;
    font-size:16px;
    font-weight:900;
    cursor:pointer;
    box-shadow:0 8px 18px rgba(8,72,167,.16);
    transition:.18s ease;
}

.signin-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 11px 22px rgba(8,72,167,.22);
}

.signin-btn svg{
    position:absolute;
    right:17px;
}

.divider{
    display:flex;
    align-items:center;
    gap:13px;
    margin:22px 0 17px;
    color:#6b7890;
    font-size:12px;
    font-weight:800;
}

.divider:before,
.divider:after{
    content:"";
    height:1px;
    flex:1;
    background:#d7e0ea;
}

.credentials{
    display:flex;
    align-items:center;
    gap:13px;
    padding:13px 15px;
    border-radius:10px;
    background:linear-gradient(90deg,#eff7ff,#f5f9fc);
    color:#16345e;
}

.credentials-icon{
    width:42px;
    height:42px;
    flex:0 0 42px;
    display:grid;
    place-items:center;
    border-radius:50%;
    background:#dcecff;
    color:#0961df;
}

.credentials strong{
    display:block;
    margin-bottom:3px;
    font-size:13px;
}

.credentials span{
    display:block;
    color:#687b96;
    font-size:12px;
}

.login-footer{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    margin-top:20px;
    color:#355273;
    font-size:12px;
    font-weight:700;
}

.login-footer b{color:var(--orange)}

.orange-line{
    width:38px;
    height:3px;
    margin:10px auto 0;
    border-radius:4px;
    background:var(--orange);
}

/* Keep the card readable on shorter laptop screens. */
@media (max-height:760px) and (min-width:901px){
    .login-page{padding-top:18px;padding-bottom:18px}
    .login-card{padding:22px 32px 20px}
    .logo-wrap{margin-bottom:12px}
    .logo-wrap img{width:min(230px,65%)}
    .login-title{font-size:30px}
    .login-subtitle{margin-bottom:16px}
    .field{margin-bottom:12px}
    .input-shell input,.signin-btn{height:46px}
    .divider{margin:16px 0 12px}
    .login-footer{margin-top:14px}
}

/* Tablet: background remains, card becomes slightly more compact. */
@media (max-width:1100px){
    .login-page{
        justify-content:flex-end;
        padding:28px;
        background-position:46% center;
    }

    .login-card{
        width:min(445px,100%);
        padding:28px 30px 24px;
    }

    .logo-wrap img{width:min(250px,72%)}
}

/* Mobile: keep transport background visible, add a dark veil for contrast. */
@media (max-width:760px){
    .login-page{
        justify-content:center;
        padding:20px 14px;
        background-position:38% center;
    }

    .login-page:before{
        content:"";
        position:absolute;
        inset:0;
        background:rgba(2,29,71,.38);
    }

    .login-card{
        position:relative;
        z-index:1;
        width:100%;
        max-width:430px;
        border-radius:18px;
        padding:25px 22px 22px;
    }

    .login-title{font-size:31px}
    .login-subtitle{font-size:14px}
}

@media (max-width:440px){
    .login-row{
        align-items:flex-start;
        flex-direction:column;
        gap:10px;
    }

    .logo-wrap img{width:min(235px,78%)}
}
</style>
</head>

<body>
<div class="login-page">
    <main class="login-card">
        <div class="logo-wrap">
            <img src="{{ asset('images/bgt-logo.png') }}" alt="BGT Bombay Goods Transport Company">
        </div>

        <h1 class="login-title">Sign In</h1>
        <p class="login-subtitle">Use your administrator account to continue.</p>

        @if($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf

            <div class="field">
                <label for="loginEmail">Email</label>
                <div class="input-shell">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m4 7 8 6 8-6"></path>
                    </svg>
                    <input
                        id="loginEmail"
                        type="email"
                        name="email"
                        value="{{ old('email','admin@gmail.com') }}"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@gmail.com"
                    >
                </div>
            </div>

            <div class="field">
                <label for="loginPassword">Password</label>
                <div class="input-shell">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    </svg>

                    <input
                        id="loginPassword"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••"
                    >

                    <button type="button" class="eye-button" id="togglePassword" aria-label="Show password">
                        <svg id="eyeOpen" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="2.7"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="login-row">
                <label class="remember">
                    <input type="checkbox" name="remember" value="1">
                    <span>Keep me signed in</span>
                </label>

                <a class="forgot" href="#" onclick="return false;">Forgot Password?</a>
            </div>

            <button class="signin-btn" type="submit">
                <span>Sign In</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14"></path>
                    <path d="m14 7 5 5-5 5"></path>
                </svg>
            </button>
        </form>

        <div class="divider"><span>OR</span></div>

        <div class="credentials">
            <div class="credentials-icon">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                </svg>
            </div>
            <div>
                <strong>Default Login Credentials</strong>
                <span>admin@gmail.com / 123456</span>
            </div>
        </div>

        <div class="login-footer">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="#173967" stroke-width="1.8">
                <path d="M3 16h14l2 2h2v-5l-2-2h-3l-2-4H7l-2 4H3z"></path>
                <circle cx="7" cy="18" r="2"></circle>
                <circle cx="17" cy="18" r="2"></circle>
            </svg>
            <span>Trusted Transport Solutions for <b>Your Business</b></span>
        </div>
        <div class="orange-line"></div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('togglePassword');
    const input = document.getElementById('loginPassword');

    button?.addEventListener('click', function () {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>
</body>
</html>
