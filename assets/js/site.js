// Thai News global UI — 2026-09-22
'use strict';
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('select[data-auto-submit]').forEach(select=>{
    select.addEventListener('change',()=>select.form?.requestSubmit());
  });
  const themeToggle=document.querySelector('.theme-toggle');
  const applyTheme=theme=>{
    document.documentElement.dataset.theme=theme;
    themeToggle?.setAttribute('aria-pressed',theme==='dark'?'true':'false');
  };
  applyTheme(document.documentElement.dataset.theme==='dark'?'dark':'light');
  themeToggle?.addEventListener('click',()=>{
    const next=document.documentElement.dataset.theme==='dark'?'light':'dark';
    applyTheme(next);
    try{localStorage.setItem('thai-news-theme',next);}catch(error){}
  });

  if('serviceWorker'in navigator){
    window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js',{scope:'/'}).catch(()=>{}));
  }

  const installPanel=document.querySelector('#pwa-install');
  const installButton=document.querySelector('#pwa-install-button');
  const iosHint=document.querySelector('#pwa-ios-hint');
  let deferredInstallPrompt=null;
  let installMode='';
  const standalone=window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
  const ios=/iphone|ipad|ipod/i.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);
  const showInstallButton=mode=>{
    if(standalone||!installPanel||!installButton)return;
    installMode=mode;
    installPanel.classList.add('is-visible');
    installButton.hidden=false;
  };
  if(ios)showInstallButton('ios');
  window.addEventListener('beforeinstallprompt',event=>{
    event.preventDefault();
    deferredInstallPrompt=event;
    showInstallButton('prompt');
  });
  installButton?.addEventListener('click',async()=>{
    if(installMode==='ios'){
      installButton.hidden=true;
      if(iosHint)iosHint.hidden=false;
      window.setTimeout(()=>{
        installPanel?.classList.remove('is-visible');
        if(iosHint)iosHint.hidden=true;
      },12000);
      return;
    }
    if(!deferredInstallPrompt)return;
    await deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt=null;
    installButton.hidden=true;
    installPanel?.classList.remove('is-visible');
  });
  window.addEventListener('appinstalled',()=>{
    deferredInstallPrompt=null;
    if(installButton)installButton.hidden=true;
    installPanel?.classList.remove('is-visible');
  });
});
