<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --danger:#ff3b30; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.64); --dim:rgba(255,255,255,.42); }
    html, body { min-height:100%; background:radial-gradient(circle at 15% 14%, rgba(112,0,255,.14), transparent 29%), radial-gradient(circle at 78% 8%, rgba(0,229,255,.14), transparent 34%), #050505; color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
    .main { min-height:100vh; margin-right:245px; }
    .content { padding:28px 34px 46px; max-width:1040px; margin:0 auto; }
    .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
    h1 { font-size:32px; font-weight:900; color:var(--primary); text-shadow:0 0 18px rgba(0,229,255,.42); }
    .page-head p { color:var(--muted); font-size:14px; margin-top:6px; font-weight:700; }
    .form-shell, .panel { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.34); }
    .form-section { padding:25px; border-bottom:1px solid rgba(255,255,255,.08); }
    .section-head { display:flex; align-items:center; gap:13px; margin-bottom:18px; }
    .section-icon, .card-icon { width:46px; height:46px; border-radius:16px; background:#000; border:1px solid var(--primary); color:var(--primary); display:grid; place-items:center; font-size:21px; box-shadow:0 0 18px rgba(0,229,255,.28); flex-shrink:0; }
    .section-head h2 { font-size:18px; font-weight:900; }
    .section-head p { color:var(--dim); font-size:12px; font-weight:700; margin-top:4px; }
    .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:15px; }
    .form-group.full { grid-column:1 / -1; }
    .form-label { display:flex; align-items:center; gap:7px; color:rgba(255,255,255,.72); font-size:12px; font-weight:900; margin-bottom:8px; }
    input, select { width:100%; border:1px solid var(--border); background:rgba(255,255,255,.055); border-radius:16px; color:#fff; padding:12px 14px; outline:none; font-family:inherit; font-size:13px; font-weight:700; }
    select option { color:#111; }
    input:focus, select:focus { border-color:var(--primary); box-shadow:0 0 18px rgba(0,229,255,.22); }
    input[type="url"], input[type="number"] { direction:ltr; text-align:left; }
    .upload-box { position:relative; display:flex; align-items:center; gap:14px; min-height:94px; padding:16px; border:1px dashed rgba(0,229,255,.35); border-radius:20px; background:rgba(0,0,0,.22); cursor:pointer; }
    .upload-box input { position:absolute; inset:0; opacity:0; cursor:pointer; }
    .card-title { display:block; font-size:14px; font-weight:900; }
    .card-sub { display:block; color:var(--dim); font-size:11px; font-weight:700; margin-top:4px; }
    .switch-card { display:flex; align-items:center; justify-content:space-between; gap:16px; border:1px solid var(--border); border-radius:22px; background:rgba(0,0,0,.22); padding:16px; }
    .card-copy { display:flex; align-items:center; gap:13px; }
    .switch { position:relative; width:58px; height:32px; flex-shrink:0; }
    .switch input { opacity:0; width:0; height:0; }
    .slider { position:absolute; inset:0; cursor:pointer; background:rgba(255,255,255,.08); border:1px solid var(--border); border-radius:999px; transition:all .3s ease; }
    .slider::before { content:''; position:absolute; width:24px; height:24px; right:4px; top:3px; border-radius:50%; background:rgba(255,255,255,.72); transition:all .3s ease; }
    .switch input:checked + .slider { background:rgba(37,211,102,.22); border-color:var(--green); }
    .switch input:checked + .slider::before { transform:translateX(-26px); background:var(--green); }
    .form-actions, .actions { display:flex; gap:12px; padding:20px 25px; border-top:1px solid rgba(255,255,255,.08); background:rgba(0,0,0,.22); }
    .actions { padding:0; border:0; background:transparent; margin-top:20px; }
    .btn { border:1px solid var(--border); min-height:44px; padding:0 18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#fff; background:rgba(255,255,255,.055); font-family:inherit; font-size:13px; font-weight:900; text-decoration:none; cursor:pointer; }
    .btn-primary { border:0; color:#001014; background:linear-gradient(135deg, var(--primary), var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
    .alert { margin-bottom:18px; padding:16px; border:1px solid rgba(255,59,48,.35); background:rgba(255,59,48,.08); border-radius:18px; }
    .alert ul { margin:8px 20px 0; color:rgba(255,255,255,.78); font-size:13px; }
    .preview { width:100%; max-height:420px; border-radius:22px; border:1px solid var(--border); background:#000; object-fit:contain; }
    .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-top:18px; }
    .detail-box { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.22); padding:14px; }
    .label { color:var(--primary); font-size:12px; font-weight:900; display:block; margin-bottom:6px; }
    .value { color:#fff; font-size:14px; font-weight:800; overflow-wrap:anywhere; }
    @media(max-width:900px) { .main{margin-right:0;} .content{padding:20px 16px 34px;} .form-grid,.detail-grid{grid-template-columns:1fr;} .page-head,.form-actions,.actions,.switch-card{flex-direction:column;align-items:stretch;} .btn{width:100%;} }
</style>
