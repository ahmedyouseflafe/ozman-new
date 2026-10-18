<style>
.watch-catalog{display:grid;grid-template-columns:110px minmax(0,1fr);gap:12px 20px;align-items:center;margin-top:16px;padding:20px;border:1px solid var(--line);border-radius:24px;background:#1c111d}
.watch-catalog>img{grid-row:1/3;width:110px;height:145px;object-fit:contain;border-radius:16px;background:#f6f4ef}
.watch-catalog-intro,.watch-catalog-controls{min-width:0}
.watch-catalog h2{margin:0 0 6px;font-size:23px;line-height:1.5}
.watch-catalog p,.watch-catalog small{font-size:12px;line-height:1.8;color:#dac7d5}
.watch-catalog-intro p{margin:0}
.watch-catalog label,.watch-catalog small{display:block}
.watch-catalog label{font-size:13px}
.watch-catalog select{display:block;width:100%;min-width:0;max-width:100%;min-height:48px;margin:8px 0;padding:10px;background:#27152c;color:#fff;border:1px solid var(--line);border-radius:12px;font:inherit;font-size:16px;text-overflow:ellipsis}
.watch-catalog #watchCatalogName{margin:8px 0 12px;color:#fff;overflow-wrap:anywhere;text-align:start}
.watch-catalog button{min-height:48px;padding:12px 18px;border:0;border-radius:12px;background:#ff86bb;color:#210615;font:inherit;cursor:pointer;white-space:normal}
.watch-catalog small{margin-top:10px}
.watch-product-try{width:100%;min-height:44px;margin-top:10px;padding:8px;border:1px solid #ff86bb80;border-radius:10px;background:#27152c;color:#ffb8d7;font:inherit;font-size:12px;cursor:pointer;white-space:normal}
.watch-tryon-open{overflow:hidden}
.watch-tryon-open body{position:fixed;inset-inline:0;width:100%;overflow:hidden}
#tryOnLayer{position:fixed;inset:0;z-index:150;display:none;align-items:center;justify-content:center;padding:14px;background:#000b;direction:rtl}
#tryOnLayer.open{display:flex}
#tryOnLayer .photo-try-card{min-width:0;max-height:calc(100dvh - 28px);border-color:#ff86bb80;overscroll-behavior:contain;overflow-wrap:anywhere}
#tryOnLayer .photo-try-card header{gap:10px}
#tryOnLayer .photo-try-card header>div{min-width:0}
#tryOnLayer .photo-try-card header small{display:none}
#tryOnLayer .photo-try-actions button{background:#ff86bb;border-color:#ff86bb;color:#210615}
#tryOnLayer small,#tryOnLayer output{color:#ff86bb}
#tryOnLayer .x{display:grid;place-items:center;width:44px;height:44px;border-radius:50%;border:1px solid #ffffff40;background:#252529;font-size:24px;cursor:pointer}
#tryOnLayer .photo-try-garment img{flex:none}
#tryOnLayer .photo-try-garment span{min-width:0;overflow-wrap:anywhere}
#tryOnLayer .photo-upload select{font-size:16px;text-overflow:ellipsis;min-height:48px}
#tryOnLayer .photo-try-consent input{flex:none;width:20px;height:20px}
#tryOnLayer .photo-try-submit{position:sticky;bottom:-20px;z-index:6;margin:12px -20px -20px;padding:12px 20px;background:#18181b;border-top:1px solid #ffffff25}
#tryOnLayer .photo-try-submit button{width:100%;min-height:48px}
@media(max-width:600px){
 .watch-catalog{grid-template-columns:80px minmax(0,1fr);gap:14px;margin-top:10px;padding:14px;border-radius:20px}
 .watch-catalog>img{grid-row:1;width:80px;height:100px}
 .watch-catalog h2{font-size:19px}
 .watch-catalog-controls{grid-column:1/-1}
 .watch-catalog button{width:100%}
 #tryOnLayer{padding:0}
 #tryOnLayer .photo-try-card{width:100%;height:100vh;height:100dvh;max-height:100vh;max-height:100dvh;border:0;border-radius:0;padding:0 16px;scroll-padding-block:80px 90px}
 #tryOnLayer .photo-try-card header{top:0;margin-inline:-16px;padding:calc(10px + env(safe-area-inset-top,0px)) 16px 10px}
 #tryOnLayer .photo-try-card h2{font-size:20px}
 #tryOnLayer .photo-try-card p,#tryOnLayer .photo-try-card label{font-size:13px}
 #tryOnLayer .photo-try-card details{margin:10px 0}
 #tryOnLayer .photo-try-stage{min-height:0}
 #tryOnLayer .photo-try-stage video,#tryOnLayer .photo-try-stage>img{max-height:42dvh}
 #tryOnLayer .photo-try-actions button{flex:1;min-height:48px}
 #tryOnLayer .photo-try-submit{bottom:0;margin:12px -16px 0;padding:10px 16px calc(10px + env(safe-area-inset-bottom,0px))}
}
</style>
