<style>
    /*
        Gaya halaman masuk.

        Dipisah dari gaya utama karena halaman ini tidak memakai kerangka
        aplikasi: tanpa sidebar, tanpa topbar, memenuhi layar.

        Seluruh hiasannya gradien dan SVG sebaris, tanpa satu pun aset dari
        luar, sehingga tidak mungkin gagal dimuat.
    */

    .auth-body{min-height:100vh;margin:0;background:var(--surface)}

    /* ---- Panggung: latar tosca dengan lambang samar ------------------ */

    .auth-stage{
        min-height:100vh;
        display:grid;
        place-items:center;
        padding:40px 20px;
        position:relative;
        overflow:hidden;
        background:
            radial-gradient(120% 80% at 20% 0%, #17708a 0%, transparent 55%),
            radial-gradient(100% 70% at 100% 100%, #0f6b80 0%, transparent 52%),
            linear-gradient(160deg, #125f72 0%, #0d4757 60%, #092f3c 100%);
    }

    .auth-watermark{
        position:absolute;
        top:50%;left:50%;
        transform:translate(-50%,-50%);
        width:min(720px,112vw);
        opacity:.055;
        pointer-events:none;
    }

    .auth-arcs{position:absolute;inset:0;opacity:.45;pointer-events:none}

    /* ---- Kartu formulir --------------------------------------------- */

    .auth-card{
        position:relative;z-index:1;
        width:100%;max-width:430px;
        background:var(--surface);
        border-radius:20px;
        box-shadow:0 30px 70px rgba(3,28,36,.36);
        padding:38px 36px 32px;
    }

    .auth-head{text-align:center;margin-bottom:26px}
    .auth-logo{
        width:68px;height:68px;
        margin:0 auto 15px;
        display:grid;place-items:center;
        border-radius:19px;
        background:var(--surface-soft);
        border:1px solid var(--line);
    }
    .auth-logo img{width:46px;height:auto;display:block}
    .auth-head strong{display:block;font-size:1.46rem;letter-spacing:.09em;color:var(--tosca-deep)}
    .auth-head .auth-institution{display:block;font-size:.84rem;color:var(--muted);margin-top:4px}
    .auth-head h1{font-size:1.12rem;margin:22px 0 6px;color:var(--text);letter-spacing:-.01em;font-weight:650}
    .auth-head p{margin:0;color:var(--muted);font-size:.88rem;line-height:1.6}

    /* ---- Kolom isian -------------------------------------------------- */

    .auth-field{margin-bottom:17px}
    .auth-field label{
        display:block;
        font-size:.8rem;font-weight:600;letter-spacing:.03em;
        text-transform:uppercase;color:var(--text-soft);
        margin-bottom:7px;
    }
    .auth-field .control{height:46px}

    .auth-password{position:relative}
    .auth-password .control{padding-right:46px}
    .auth-eye{
        position:absolute;top:50%;right:6px;transform:translateY(-50%);
        width:36px;height:36px;
        display:grid;place-items:center;
        border:0;border-radius:9px;
        background:transparent;color:var(--muted);cursor:pointer;
    }
    .auth-eye:hover{background:var(--surface-soft);color:var(--tosca)}
    .auth-eye svg{width:18px;height:18px}

    .auth-submit{width:100%;height:47px;margin-top:9px;justify-content:center;font-size:.96rem}

    /* ---- Pesan -------------------------------------------------------- */

    .auth-alert{
        display:flex;gap:11px;
        padding:13px 15px;border-radius:var(--r-md);
        background:rgba(214,69,69,.07);
        border:1px solid rgba(214,69,69,.24);
        color:#9d2f2f;font-size:.88rem;line-height:1.55;
        margin-bottom:22px;text-align:left;
    }
    .auth-alert ul{margin:5px 0 0;padding-left:17px}
    .auth-alert svg{width:18px;height:18px;flex:none;margin-top:1px}

    .auth-note{
        display:flex;gap:11px;
        padding:13px 15px;border-radius:var(--r-md);
        background:var(--surface-soft);
        border:1px solid var(--line);
        color:var(--text-soft);font-size:.86rem;line-height:1.6;
        margin-bottom:22px;text-align:left;
    }
    .auth-note svg{width:18px;height:18px;flex:none;margin-top:1px;color:var(--tosca)}

    .auth-help{
        margin-top:26px;padding-top:20px;
        border-top:1px solid var(--line);
        font-size:.83rem;color:var(--muted);line-height:1.65;
        text-align:center;
    }

    .auth-foot{
        position:relative;z-index:1;
        margin:22px auto 0;
        text-align:center;
        color:#a5cfda;font-size:.78rem;line-height:1.6;
        max-width:430px;
    }

    /* ---- Layar sempit -------------------------------------------------- */

    @media (max-width:520px){
        .auth-stage{padding:28px 16px}
        .auth-card{padding:30px 22px 26px;border-radius:17px}
        .auth-head strong{font-size:1.3rem}
        .auth-logo{width:60px;height:60px}
        .auth-logo img{width:40px}
    }
</style>
