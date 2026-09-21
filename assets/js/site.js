// Thai News global UI — 2026-09-21
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
});
