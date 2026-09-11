{{-- Shared install-prompt + service-worker registration.
     Include with: sw (service worker filename), storageKey (localStorage dismiss key), appLabel.
     Optional: bannerBottom (CSS length the iOS banner sits above, e.g. a mobile bottom-nav bar height; defaults to '1rem'). --}}
@php($bannerBottom = $bannerBottom ?? '1rem')
<script>
if('serviceWorker'in navigator)window.addEventListener('load',()=>navigator.serviceWorker.register(@json(asset($sw))).catch(()=>{}));

(function(){
    const isStandalone=window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
    if(isStandalone)return;

    const installBtn=document.querySelector('[data-pwa-install]');
    let deferredPrompt=null;
    window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();deferredPrompt=event;installBtn?.classList.remove('js-hidden')});
    installBtn?.addEventListener('click',async()=>{
        if(!deferredPrompt)return;
        installBtn.classList.add('js-hidden');
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt=null;
    });
    window.addEventListener('appinstalled',()=>installBtn?.classList.add('js-hidden'));

    const isIos=/iphone|ipad|ipod/i.test(navigator.userAgent);
    const dismissedAt=Number(localStorage.getItem(@json($storageKey))||0);
    const shouldPromptIos=isIos&&window.matchMedia('(max-width:1023px)').matches&&Date.now()-dismissedAt>14*24*60*60*1000;
    if(shouldPromptIos){
        const banner=document.createElement('div');
        banner.className='fixed inset-x-2 z-[10000] rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl lg:hidden';
        banner.style.bottom='calc({{ $bannerBottom }} + env(safe-area-inset-bottom))';
        banner.innerHTML='<div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-50 text-base">⬆️</span><div class="min-w-0 flex-1"><p class="text-xs font-black text-slate-900">Install '+@json($appLabel)+'</p><p class="mt-0.5 text-[11px] font-semibold text-slate-500">Tap Share, then "Add to Home Screen" for one-tap access.</p></div><button type="button" class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-100 text-sm font-black text-slate-500" aria-label="Dismiss">×</button></div>';
        banner.querySelector('button').addEventListener('click',()=>{localStorage.setItem(@json($storageKey),String(Date.now()));banner.remove()});
        document.body.appendChild(banner);
    }
})();
</script>
