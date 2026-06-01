<style>
    * { box-sizing:border-box; margin:0; padding:0; }
    :root { --primary:#00e5ff; --accent:#7000ff; --green:#25d366; --danger:#ff3b30; --border:rgba(255,255,255,.1); --text:#fff; --muted:rgba(255,255,255,.64); --dim:rgba(255,255,255,.42); }
    html,body { min-height:100%; background:radial-gradient(circle at 15% 14%, rgba(112,0,255,.14), transparent 29%), radial-gradient(circle at 78% 8%, rgba(0,229,255,.14), transparent 34%), #050505; color:var(--text); font-family:'Cairo','Segoe UI',Tahoma,sans-serif; direction:rtl; }
    .main { min-height:100vh; margin-right:245px; }
    .content { padding:28px 34px 46px; max-width:1040px; margin:0 auto; }
    .page-head { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; margin-bottom:22px; }
    h1 { font-size:32px; font-weight:900; color:var(--primary); text-shadow:0 0 18px rgba(0,229,255,.42); }
    .page-head p { color:var(--muted); font-size:14px; margin-top:6px; font-weight:700; }
    .form-shell,.panel { border:1px solid var(--border); background:linear-gradient(145deg, rgba(255,255,255,.07), rgba(255,255,255,.025)); backdrop-filter:blur(16px); border-radius:26px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.34); }
    .form-section { padding:25px; border-bottom:1px solid rgba(255,255,255,.08); }
    .section-head { display:flex; align-items:center; gap:13px; margin-bottom:18px; }
    .section-icon,.card-icon { width:46px; height:46px; border-radius:16px; background:#000; border:1px solid var(--primary); color:var(--primary); display:grid; place-items:center; font-size:21px; box-shadow:0 0 18px rgba(0,229,255,.28); flex-shrink:0; }
    .section-head h2 { font-size:18px; font-weight:900; }
    .section-head p { color:var(--dim); font-size:12px; font-weight:700; margin-top:4px; }
    .form-grid,.detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:15px; }
    .form-group.full { grid-column:1 / -1; }
    .form-group:has(input[type="hidden"]) { display:none; }
    .form-group.full:has(.location-card) { display:block; }
    .form-label { display:flex; align-items:center; gap:7px; color:rgba(255,255,255,.72); font-size:12px; font-weight:900; margin-bottom:8px; }
    input,select { width:100%; border:1px solid var(--border); background:rgba(255,255,255,.055); border-radius:16px; color:#fff; padding:12px 14px; outline:none; font-family:inherit; font-size:13px; font-weight:700; }
    select option { color:#111; }
    input:focus,select:focus { border-color:var(--primary); box-shadow:0 0 18px rgba(0,229,255,.22); }
    input[type="number"],input[dir="ltr"] { direction:ltr; text-align:left; }
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
    .location-card { display:flex; align-items:center; justify-content:space-between; gap:16px; border:1px solid var(--border); border-radius:22px; background:rgba(0,0,0,.22); padding:16px; }
    .map-status { color:var(--primary); font-weight:900; text-shadow:0 0 10px rgba(0,229,255,.35); }
    .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.78); backdrop-filter:blur(10px); display:none; align-items:center; justify-content:center; padding:20px; z-index:1000; }
    .modal-backdrop.open { display:flex; }
    .location-modal { width:min(980px,100%); border:1px solid rgba(0,229,255,.28); background:rgba(5,5,5,.96); border-radius:28px; overflow:hidden; box-shadow:0 24px 80px rgba(0,0,0,.55), 0 0 35px rgba(0,229,255,.16); }
    .modal-head,.modal-footer { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:18px 20px; border-bottom:1px solid var(--border); }
    .modal-footer { border-bottom:0; border-top:1px solid var(--border); background:rgba(255,255,255,.035); }
    .modal-title { display:flex; align-items:center; gap:12px; }
    .modal-title strong { display:block; font-size:16px; font-weight:900; }
    .modal-title span span,.picked-location { color:var(--muted); font-size:12px; font-weight:700; margin-top:3px; }
    .modal-close { width:40px; height:40px; border-radius:50%; border:1px solid var(--border); background:rgba(255,255,255,.055); color:#fff; display:grid; place-items:center; cursor:pointer; transition:all .3s ease; }
    .modal-close:hover { color:var(--danger); border-color:var(--danger); transform:rotate(90deg); }
    .modal-body { padding:18px; }
    .coordinate-search { display:grid; grid-template-columns:1fr 1fr auto; gap:12px; margin-bottom:12px; align-items:end; }
    .coordinate-error { display:none; color:var(--danger); font-size:12px; font-weight:800; margin:0 0 12px; }
    .coordinate-error.show { display:block; }
    #agentLocationMap { width:100%; height:440px; border:1px solid var(--border); border-radius:22px; overflow:hidden; background:#111; }
    .leaflet-container,.leaflet-control { font-family:'Cairo',sans-serif; }
    .form-actions,.actions { display:flex; gap:12px; padding:20px 25px; border-top:1px solid rgba(255,255,255,.08); background:rgba(0,0,0,.22); }
    .actions { padding:0; border:0; background:transparent; margin-top:20px; }
    .btn { border:1px solid var(--border); min-height:44px; padding:0 18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; gap:8px; color:#fff; background:rgba(255,255,255,.055); font-family:inherit; font-size:13px; font-weight:900; text-decoration:none; cursor:pointer; }
    .btn-primary { border:0; color:#001014; background:linear-gradient(135deg,var(--primary),var(--accent)); box-shadow:0 0 22px rgba(0,229,255,.34); }
    .alert { margin-bottom:18px; padding:16px; border:1px solid rgba(255,59,48,.35); background:rgba(255,59,48,.08); border-radius:18px; }
    .alert ul { margin:8px 20px 0; color:rgba(255,255,255,.78); font-size:13px; }
    .detail-box { border:1px solid var(--border); border-radius:18px; background:rgba(0,0,0,.22); padding:14px; }
    .label { color:var(--primary); font-size:12px; font-weight:900; display:block; margin-bottom:6px; }
    .value { color:#fff; font-size:14px; font-weight:800; overflow-wrap:anywhere; }
    .avatar { width:120px; height:120px; border-radius:28px; border:2px solid var(--primary); object-fit:cover; background:#000; display:grid; place-items:center; color:var(--primary); font-size:42px; margin-bottom:18px; }
    .tag { display:inline-flex; align-items:center; gap:7px; min-height:32px; border-radius:999px; padding:0 12px; border:1px solid currentColor; font-size:12px; font-weight:900; }
    .tag-g { color:var(--green); background:rgba(37,211,102,.1); }
    .tag-r { color:var(--danger); background:rgba(255,59,48,.1); }
    @media(max-width:900px){.main{margin-right:0}.content{padding:20px 16px 34px}.form-grid,.detail-grid,.coordinate-search{grid-template-columns:1fr}.page-head,.form-actions,.actions,.switch-card,.location-card,.modal-footer{flex-direction:column;align-items:stretch}.btn{width:100%}}
</style>
