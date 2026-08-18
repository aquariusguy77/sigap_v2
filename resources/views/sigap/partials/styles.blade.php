<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root{
        --surface:#ffffff;
        --surface-soft:#f2fafb;
        --text:#0d3b45;
        --text-soft:#3d6a75;
        --muted:#6b8792;
        --line:#e2edf0;
        --line-soft:#eef5f7;
        --tosca:#0e8ba8;
        --tosca-deep:#125f72;
        --tosca-tint:rgba(14,139,168,.09);
        --gold:#c8951a;
        --gold-deep:#9a710f;
        --gold-tint:rgba(200,149,26,.12);
        --green:#0f9184;
        --danger:#d64545;
        --warn:#d3860f;
        --shadow-xs:0 1px 2px rgba(13,59,69,.05);
        --shadow-sm:0 2px 8px rgba(13,59,69,.06);
        --shadow:0 8px 24px rgba(13,59,69,.07);
        --sidebar-width:252px;
        --topbar-height:62px;
        --r-sm:8px;
        --r:10px;
        --r-md:12px;
        --r-lg:16px;
    }

    *{box-sizing:border-box}
    html{scroll-behavior:smooth;font-size:15px}
    body{
        margin:0;
        font-family:'Plus Jakarta Sans',Inter,"Segoe UI",sans-serif;
        font-size:.875rem;
        line-height:1.55;
        color:var(--text);
        background:#f4f8f9;
        -webkit-font-smoothing:antialiased;
    }
    a{color:inherit;text-decoration:none}
    button,input,select,textarea{font:inherit;color:inherit}
    img,svg{max-width:100%;display:block}
    h1,h2,h3,h4{margin:0;font-weight:700;letter-spacing:-.01em;line-height:1.3}

    /* ============ SHELL ============ */
    .app-shell{display:grid;grid-template-columns:var(--sidebar-width) minmax(0,1fr);min-height:100vh}

    /* ============ SIDEBAR ============ */
    .sidebar{
        position:sticky;top:0;display:flex;flex-direction:column;
        height:100vh;padding:14px 12px;
        background:linear-gradient(178deg,#0a4653 0%,#0d5c6d 100%);
        color:#e6f6f8;z-index:20;
        transition:padding .18s ease;
    }
    .brand{display:flex;align-items:center;gap:10px;padding:4px 4px 12px;min-height:46px}
    .brand-mark{
        width:44px;height:44px;flex:0 0 44px;display:grid;place-items:center;
        filter:drop-shadow(0 2px 5px rgba(0,0,0,.28));
    }
    .brand-mark img{width:100%;height:100%;object-fit:contain;display:block}
    .brand-mark svg{width:22px;height:22px;color:var(--gold)}
    .brand-text{min-width:0}
    .brand-text h1{font-size:.86rem;font-weight:700;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .brand-text p{margin:1px 0 0;font-size:.68rem;color:rgba(230,246,248,.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    .sidebar-collapse{
        margin-left:auto;width:28px;height:28px;flex:0 0 28px;display:grid;place-items:center;
        border:1px solid rgba(255,255,255,.14);border-radius:8px;
        background:rgba(255,255,255,.06);color:#cfeaef;cursor:pointer;
        transition:background .16s ease,color .16s ease;
    }
    .sidebar-collapse:hover{background:rgba(255,255,255,.14);color:#fff}
    .sidebar-collapse svg{width:15px;height:15px;transition:transform .2s ease}

    .nav-label{
        display:block;margin:10px 10px 6px;font-size:.62rem;font-weight:700;
        letter-spacing:.12em;text-transform:uppercase;color:rgba(230,246,248,.42);
    }
    .menu{
        display:grid;gap:2px;align-content:start;
        flex:1 1 auto;min-height:0;overflow-y:auto;overflow-x:hidden;padding-right:2px;
    }
    .menu::-webkit-scrollbar{width:4px}
    .menu::-webkit-scrollbar-thumb{background:rgba(255,255,255,.16);border-radius:4px}
    .menu-link{
        position:relative;display:flex;align-items:center;gap:11px;
        padding:9px 11px;border-radius:var(--r);
        color:rgba(230,246,248,.82);font-size:.83rem;font-weight:500;
        transition:background .15s ease,color .15s ease;white-space:nowrap;
    }
    .menu-link:hover{background:rgba(255,255,255,.08);color:#fff}
    .menu-link.active{background:rgba(255,255,255,.13);color:#fff;font-weight:600}
    .menu-link.active::before{
        content:"";position:absolute;left:0;top:50%;transform:translateY(-50%);
        width:3px;height:18px;border-radius:0 3px 3px 0;background:var(--gold);
    }
    .menu-icon,.section-icon,.stat-icon,.chip-icon{width:17px;height:17px;flex:0 0 17px}
    .menu-link .menu-icon{opacity:.9}

    .sidebar-footer{display:grid;gap:8px;margin-top:auto;padding-top:10px;flex-shrink:0}
    .status-card{
        padding:9px 11px;border-radius:var(--r);
        background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.09);
    }
    .status-card strong{display:block;font-size:.72rem;font-weight:600;color:#f2e2bb;margin-bottom:2px}
    .status-note{margin:0;font-size:.7rem;line-height:1.45;color:rgba(230,246,248,.62)}
    .status-note b{color:#fff;font-weight:600}
    .logout-button{
        display:flex;align-items:center;gap:10px;width:100%;
        padding:9px 11px;border-radius:var(--r);cursor:pointer;
        font-size:.82rem;font-weight:500;color:#ffd9d9;
        background:rgba(214,69,69,.16);border:1px solid rgba(214,69,69,.24);
        transition:background .16s ease;white-space:nowrap;
    }
    .logout-button:hover{background:rgba(214,69,69,.3);color:#fff}

    /* --- collapsed state --- */
    body.sidebar-collapsed{--sidebar-width:64px}
    body.sidebar-collapsed .sidebar{padding:14px 8px}
    body.sidebar-collapsed .brand{flex-direction:column;gap:8px;padding-bottom:10px}
    body.sidebar-collapsed .brand-text,
    body.sidebar-collapsed .nav-label,
    body.sidebar-collapsed .status-card,
    body.sidebar-collapsed .menu-link > span,
    body.sidebar-collapsed .logout-button > span{display:none}
    body.sidebar-collapsed .sidebar-collapse{margin-left:0}
    body.sidebar-collapsed .sidebar-collapse svg{transform:rotate(180deg)}
    body.sidebar-collapsed .menu-link,
    body.sidebar-collapsed .logout-button{justify-content:center;padding:10px 0;gap:0}
    body.sidebar-collapsed .menu-link::after{
        content:attr(data-label);position:absolute;left:calc(100% + 8px);top:50%;
        transform:translateY(-50%);padding:5px 9px;border-radius:7px;
        background:#0d3b45;color:#fff;font-size:.74rem;font-weight:500;
        white-space:nowrap;opacity:0;visibility:hidden;pointer-events:none;
        box-shadow:0 4px 14px rgba(13,59,69,.28);transition:opacity .14s ease;z-index:60;
    }
    body.sidebar-collapsed .menu-link:hover::after{opacity:1;visibility:visible}

    /* ============ TOPBAR ============ */
    .main-content{min-width:0;display:flex;flex-direction:column}
    .topbar{
        position:sticky;top:0;z-index:15;
        display:flex;align-items:center;gap:14px;
        min-height:var(--topbar-height);padding:10px 20px;
        background:rgba(255,255,255,.92);backdrop-filter:blur(10px);
        border-bottom:1px solid var(--line);
    }
    .topbar-left{display:flex;align-items:center;gap:11px;min-width:0;flex:1 1 auto}
    .topbar-right{display:flex;align-items:center;gap:10px;flex:0 0 auto}
    .sidebar-toggle{
        display:none;width:36px;height:36px;flex:0 0 36px;
        border:1px solid var(--line);border-radius:var(--r);
        background:var(--surface);color:var(--tosca-deep);cursor:pointer;
        place-items:center;
    }
    .page-title{min-width:0}
    .page-title h2{font-size:1rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .page-title p{
        margin:1px 0 0;color:var(--muted);font-size:.76rem;line-height:1.4;
        display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;
    }
    .toolbar-search{
        display:flex;align-items:center;gap:8px;
        width:230px;padding:7px 12px;border-radius:var(--r);
        background:#f6fafb;border:1px solid var(--line);
        color:var(--muted);transition:border-color .16s ease,box-shadow .16s ease,width .18s ease;
    }
    .toolbar-search input{
        width:100%;min-width:0;border:none;outline:none;background:transparent;
        font-size:.8rem;color:var(--text);
    }
    .toolbar-search input::placeholder{color:#9ab3bb}
    .toolbar-search:focus-within{
        border-color:var(--tosca);background:#fff;
        box-shadow:0 0 0 3px rgba(14,139,168,.1);
    }
    .user-chip{
        display:flex;align-items:center;gap:9px;padding:4px 10px 4px 4px;
        border-radius:var(--r);background:#f6fafb;border:1px solid var(--line);
    }
    .avatar{
        width:32px;height:32px;flex:0 0 32px;display:grid;place-items:center;border-radius:8px;
        background:linear-gradient(140deg,var(--tosca),var(--tosca-deep));
        color:#fff;font-weight:700;font-size:.74rem;
    }
    .user-chip-text{min-width:0;line-height:1.25}
    .user-chip strong{display:block;font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px}
    .user-chip small{display:block;color:var(--muted);font-size:.68rem}

    /* ============ PAGE BODY ============ */
    .page-body{padding:18px 20px 26px;flex:1 1 auto}

    /* ============ HERO ============ */
    .hero-panel{
        position:relative;overflow:hidden;
        display:grid;grid-template-columns:1.5fr .9fr;gap:18px;
        padding:20px 22px;border-radius:var(--r-lg);
        background:linear-gradient(135deg,#0d5261 0%,#12768c 100%);
        color:#fff;box-shadow:var(--shadow);
    }
    .hero-panel::after{
        content:"";position:absolute;right:-70px;top:-70px;width:220px;height:220px;border-radius:50%;
        background:radial-gradient(circle,rgba(200,149,26,.26),transparent 68%);pointer-events:none;
    }
    .eyebrow{
        display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:99px;
        background:rgba(255,255,255,.14);color:#f5e6c2;
        font-size:.68rem;font-weight:600;letter-spacing:.02em;
    }
    .hero-copy{position:relative;z-index:1;min-width:0}
    .hero-copy h3{margin:11px 0 7px;font-size:1.3rem;line-height:1.25}
    .hero-copy p{margin:0;max-width:56ch;font-size:.82rem;line-height:1.65;color:rgba(255,255,255,.78)}
    .hero-meta{display:flex;flex-wrap:wrap;gap:22px;margin-top:16px}
    .hero-meta strong{display:block;font-size:1.15rem;font-weight:700;line-height:1.1}
    .hero-meta span{font-size:.72rem;color:rgba(255,255,255,.7);line-height:1.4}
    .hero-side{position:relative;z-index:1;display:grid;gap:10px;align-content:start}
    .highlight-card{
        padding:12px 14px;border-radius:var(--r-md);
        background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.13);
    }
    .highlight-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
    .highlight-card strong{font-size:.82rem;font-weight:600}
    .highlight-card p{margin:0;font-size:.74rem;line-height:1.55;color:rgba(255,255,255,.76)}
    .highlight-card code{color:#f5e6c2;font-size:.72rem}
    .highlight-card .mini-badge{background:rgba(255,255,255,.18);color:#fff}

    /* ============ GRIDS ============ */
    .dashboard-grid,.content-grid,.triple-grid,.double-grid{display:grid;gap:14px;margin-top:14px}
    .dashboard-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    .content-grid{grid-template-columns:minmax(0,1.5fr) minmax(280px,.9fr);align-items:start}
    .double-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .triple-grid{grid-template-columns:repeat(3,minmax(0,1fr))}

    /* ============ CARDS ============ */
    .panel,.stat-card,.list-item,.timeline-item,.setting-card{
        background:var(--surface);border:1px solid var(--line);border-radius:var(--r-md);
    }
    .panel{padding:18px;box-shadow:var(--shadow-xs)}
    .stat-card{
        padding:14px 15px;box-shadow:var(--shadow-xs);
        transition:border-color .16s ease,box-shadow .16s ease;
    }
    .stat-card:hover{border-color:rgba(14,139,168,.3);box-shadow:var(--shadow-sm)}
    .stat-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
    .stat-card h4{font-size:.76rem;font-weight:600;color:var(--muted)}
    .stat-icon-wrap{width:32px;height:32px;flex:0 0 32px;display:grid;place-items:center;border-radius:8px;background:var(--tosca-tint);color:var(--tosca-deep)}
    .tone-green{color:var(--green)!important;background:rgba(15,145,132,.1)!important}
    .tone-orange{color:var(--warn)!important;background:var(--gold-tint)!important}
    .tone-deep{color:var(--gold-deep)!important;background:var(--gold-tint)!important}
    .stat-card strong{display:block;font-size:1.5rem;font-weight:700;line-height:1.1;color:var(--tosca-deep);margin-bottom:4px}
    .metric-note{
        margin:0;color:var(--muted);font-size:.73rem;line-height:1.5;
        display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
    }

    /* ============ SECTION HEAD ============ */
    .section-head,.split-header,.setting-head,.table-toolbar{
        display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;
    }
    .section-head{margin-bottom:14px}
    .section-tag{
        display:inline-flex;align-items:center;gap:6px;padding:4px 9px;border-radius:99px;
        background:var(--tosca-tint);color:var(--tosca-deep);font-size:.68rem;font-weight:600;
    }
    .section-head h3,.split-header h3,.setting-head h3{font-size:.95rem;font-weight:700;margin-top:7px}
    .section-intro,.split-header p,.setting-copy p{
        margin:3px 0 0;color:var(--muted);font-size:.78rem;line-height:1.55;max-width:70ch;
    }
    .table-toolbar p{margin:0;color:var(--muted);font-size:.76rem}

    /* ============ BADGES ============ */
    .badge,.mini-badge,.status-pill{
        display:inline-flex;align-items:center;gap:5px;border-radius:99px;
        font-size:.7rem;font-weight:600;white-space:nowrap;
    }
    .badge{padding:4px 9px;background:#eff4f6;color:var(--text-soft);border:1px solid var(--line)}
    .mini-badge{padding:3px 8px;background:var(--gold-tint);color:#8a6510}
    .status-pill{padding:5px 10px;background:var(--tosca-tint);color:var(--tosca-deep)}
    .badge.success,.mini-badge.success{background:rgba(15,145,132,.11);color:#0a6b61;border-color:rgba(15,145,132,.2)}
    .badge.warn,.mini-badge.warn{background:rgba(211,134,15,.13);color:#9b6207;border-color:rgba(211,134,15,.22)}
    .badge.danger,.mini-badge.danger{background:rgba(214,69,69,.11);color:#a33333;border-color:rgba(214,69,69,.2)}

    /* ============ FORM CONTROLS ============ */
    .field{display:grid;gap:4px;min-width:0}
    .field-label{font-size:.7rem;font-weight:600;color:var(--muted);letter-spacing:.01em}
    .control{
        width:100%;min-width:0;padding:8px 11px;
        border-radius:var(--r);border:1px solid var(--line);background:#fbfdfd;
        font-size:.81rem;outline:none;
        transition:border-color .16s ease,box-shadow .16s ease,background .16s ease;
    }
    .control:focus{border-color:var(--tosca);background:#fff;box-shadow:0 0 0 3px rgba(14,139,168,.1)}
    .control::placeholder{color:#9ab3bb}
    select.control{
        appearance:none;cursor:pointer;padding-right:28px;
        background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236b8792'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat:no-repeat;background-position:right 7px center;background-size:15px;
    }
    textarea.control{resize:vertical;min-height:76px}
    .field-error{color:var(--danger);font-size:.72rem;margin-top:2px}

    /* ============ FILTERS ============ */
    .filters{
        display:grid;grid-template-columns:repeat(auto-fit,minmax(158px,1fr));
        gap:10px;align-items:end;
        padding:13px;margin-bottom:14px;
        background:#f7fbfc;border:1px solid var(--line-soft);border-radius:var(--r-md);
    }
    .filters-actions{display:flex;gap:8px;align-items:end}
    .filters-actions .btn{flex:1 1 auto;justify-content:center}

    /* ============ BUTTONS ============ */
    .btn{
        display:inline-flex;align-items:center;justify-content:center;gap:6px;
        padding:8px 14px;border:1px solid transparent;border-radius:var(--r);
        font-size:.8rem;font-weight:600;cursor:pointer;white-space:nowrap;
        transition:background .16s ease,border-color .16s ease,color .16s ease;
    }
    .btn-primary{background:var(--tosca);color:#fff}
    .btn-primary:hover{background:var(--tosca-deep)}
    .btn-gold{background:var(--gold);color:#fff}
    .btn-gold:hover{background:var(--gold-deep)}
    .btn-ghost{background:var(--surface);color:var(--text-soft);border-color:var(--line)}
    .btn-ghost:hover{border-color:var(--tosca);color:var(--tosca-deep);background:#f7fbfc}
    .btn-danger{background:var(--danger);color:#fff}
    .btn-danger:hover{background:#b93a3a}
    .btn-sm{padding:6px 11px;font-size:.75rem}
    .btn .chip-icon{width:15px;height:15px;flex:0 0 15px}

    /* icon-only action button */
    .btn-icon{
        display:inline-grid;place-items:center;width:28px;height:28px;
        border:1px solid var(--line);border-radius:7px;background:var(--surface);
        color:var(--muted);cursor:pointer;transition:all .15s ease;
    }
    .btn-icon svg{width:15px;height:15px}
    .btn-icon:hover{border-color:var(--tosca);color:var(--tosca-deep);background:#f2fafb}
    .btn-icon.danger:hover{border-color:var(--danger);color:var(--danger);background:#fdf4f4}
    .row-actions{display:flex;gap:5px;align-items:center}

    /* ============ TABLE ============ */
    .table-wrap{overflow-x:auto;border:1px solid var(--line);border-radius:var(--r-md)}
    table{width:100%;border-collapse:collapse;font-size:.81rem}
    th,td{padding:9px 12px;text-align:left;vertical-align:middle}
    th{
        color:var(--muted);font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;
        font-weight:700;background:#f7fbfc;border-bottom:1px solid var(--line);white-space:nowrap;
    }
    td{border-bottom:1px solid var(--line-soft)}
    tbody tr:last-child td{border-bottom:none}
    tbody tr{transition:background .13s ease}
    tbody tr:hover{background:#f9fdfd}
    .cell-title{font-weight:600;font-size:.83rem;display:block;line-height:1.35}
    .table-meta{color:var(--muted);font-size:.72rem}
    .empty-row{text-align:center;color:var(--muted);padding:28px 12px!important}

    /* ============ PAGINATION ============ */
    .pager{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
    .pager a,.pager span{
        display:inline-flex;align-items:center;padding:5px 10px;border-radius:7px;
        font-size:.75rem;font-weight:600;border:1px solid var(--line);background:var(--surface);
    }
    .pager a:hover{border-color:var(--tosca);color:var(--tosca-deep)}
    .pager .is-disabled{color:#b6c8ce;background:#f7fafb}
    .pager .is-current{background:var(--tosca);color:#fff;border-color:var(--tosca)}

    /* ============ LIST / TIMELINE ============ */
    .list-group,.timeline,.settings-grid{display:grid;gap:9px}
    .list-item,.timeline-item,.setting-card{padding:12px 14px}
    .list-item strong,.timeline-item strong,.setting-card strong,
    .doc-card strong,.report-card strong,.step-card strong{
        display:block;margin-bottom:3px;font-size:.83rem;font-weight:600;
    }
    .list-item p,.timeline-item p,.setting-card p,
    .doc-card p,.report-card p,.step-card p{margin:0;color:var(--muted);font-size:.76rem;line-height:1.55}
    .list-item h3{font-size:.86rem}
    .timeline-item{display:grid;grid-template-columns:34px minmax(0,1fr);gap:11px;align-items:start}
    .timeline-mark{
        width:34px;height:34px;display:grid;place-items:center;border-radius:9px;
        background:var(--tosca-tint);color:var(--tosca-deep);
    }
    .timeline-mark svg{width:16px;height:16px}
    .timeline-meta{margin-top:4px;color:var(--muted);font-size:.7rem}
    .split{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,.85fr);gap:14px}
    .split-header{margin-bottom:10px}

    /* ============ STEP / DOC / REPORT CARDS ============ */
    .step-grid,.doc-grid,.report-grid{display:grid;gap:10px;margin-top:12px}
    .step-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    .doc-grid,.report-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .step-card,.doc-card,.report-card{
        padding:13px 14px;border-radius:var(--r-md);
        border:1px solid var(--line);background:#fbfdfd;
    }
    .step-card.active{border-color:rgba(14,139,168,.32);background:var(--tosca-tint)}
    .step-index{
        display:inline-grid;place-items:center;width:26px;height:26px;margin-bottom:8px;
        border-radius:7px;background:var(--tosca-tint);color:var(--tosca-deep);
        font-weight:700;font-size:.78rem;
    }
    .step-card.active .step-index{background:var(--tosca);color:#fff}
    .setting-card{display:flex;gap:12px;align-items:flex-start}
    .section-icon-wrap{display:grid;place-items:center;border-radius:9px;flex-shrink:0}

    /* ============ SUBTLE BOX ============ */
    .subtle-box{
        margin-top:12px;padding:13px 15px;border-radius:var(--r-md);
        background:#f7fbfc;border:1px solid var(--line-soft);
    }
    .subtle-box h4{margin:0 0 6px;font-size:.81rem;font-weight:600}
    .subtle-box ul{margin:0;padding-left:16px;color:var(--muted);font-size:.76rem;line-height:1.7}
    .subtle-box code,.list-item code{
        background:#e8f3f5;padding:1px 5px;border-radius:4px;
        font-size:.72rem;color:var(--tosca-deep);word-break:break-all;
    }

    /* ============ FLASH ============ */
    .flash{
        display:flex;align-items:flex-start;gap:9px;
        margin-bottom:14px;padding:10px 14px;border-radius:var(--r);
        background:rgba(15,145,132,.08);border:1px solid rgba(15,145,132,.22);
        color:#0a6b61;font-size:.81rem;font-weight:500;
    }
    .flash .chip-icon{flex-shrink:0;margin-top:2px}
    .alert-error{background:rgba(214,69,69,.07);border-color:rgba(214,69,69,.22);color:#a33333}
    .alert-error strong{display:block;margin-bottom:3px;font-size:.81rem}
    .alert-error ul{margin:0;padding-left:16px;font-weight:400;font-size:.78rem;line-height:1.6}

    /* ============ FOOTER ============ */
    .footer{
        margin-top:20px;padding:14px 2px 0;border-top:1px solid var(--line);
        color:var(--muted);font-size:.73rem;
    }
    .footer strong{color:var(--text-soft);font-weight:600}
    .overlay{display:none}
    .section-anchor{scroll-margin-top:80px}

    /* ============ RESPONSIVE ============ */
    @media (max-width:1180px){
        .dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .step-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .content-grid,.split,.double-grid,.doc-grid,.report-grid,.triple-grid,.hero-panel{grid-template-columns:1fr}
        .toolbar-search{width:186px}
    }
    @media (max-width:980px){
        .app-shell{grid-template-columns:1fr}
        .sidebar{
            position:fixed;inset:0 auto 0 0;width:min(80vw,268px);
            transform:translateX(-100%);transition:transform .24s ease;
        }
        .sidebar.open{transform:translateX(0)}
        body.sidebar-collapsed{--sidebar-width:252px}
        body.sidebar-collapsed .sidebar{padding:14px 12px}
        body.sidebar-collapsed .brand{flex-direction:row}
        body.sidebar-collapsed .brand-text,
        body.sidebar-collapsed .nav-label,
        body.sidebar-collapsed .status-card,
        body.sidebar-collapsed .menu-link > span,
        body.sidebar-collapsed .logout-button > span{display:block}
        body.sidebar-collapsed .menu-link,
        body.sidebar-collapsed .logout-button{justify-content:flex-start;padding:9px 11px;gap:11px}
        body.sidebar-collapsed .menu-link::after{display:none}
        .sidebar-collapse{display:none}
        .sidebar-toggle{display:grid}
        .overlay.visible{position:fixed;inset:0;background:rgba(8,40,48,.45);z-index:18;display:block}
        .topbar{padding:10px 15px}
        .page-body{padding:14px 15px 22px}
        .user-chip-text{display:none}
        .user-chip{padding:4px}
    }
    @media (max-width:640px){
        .dashboard-grid,.step-grid,.filters{grid-template-columns:1fr}
        .hero-panel,.panel{padding:15px}
        .hero-copy h3{font-size:1.12rem}
        .hero-meta{gap:14px}
        .toolbar-search{display:none}
        .page-title p{display:none}
        .filters-actions{grid-column:1/-1}
    }
</style>
