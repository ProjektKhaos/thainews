// Thai News first-visit guided tours — 2026-09-22
'use strict';

document.addEventListener('DOMContentLoaded',()=>{
  const configNode=document.querySelector('#tour-config');
  const layer=document.querySelector('#tour-layer');
  const spotlight=document.querySelector('#tour-spotlight');
  const dialog=document.querySelector('#tour-dialog');
  const title=document.querySelector('#tour-title');
  const body=document.querySelector('#tour-body');
  const counter=document.querySelector('#tour-counter');
  const skipButton=document.querySelector('#tour-skip');
  const backButton=document.querySelector('#tour-back');
  const nextButton=document.querySelector('#tour-next');
  const restartButton=document.querySelector('#tour-restart');
  if(!configNode||!layer||!spotlight||!dialog||!title||!body||!counter||!skipButton||!backButton||!nextButton)return;

  let config;
  try{config=JSON.parse(configNode.textContent||'{}');}catch(error){return;}
  const steps=(Array.isArray(config.steps)?config.steps:[])
    .map(step=>({...step,target:document.querySelector(step.selector)}))
    .filter(step=>step.target instanceof HTMLElement);
  if(!steps.length)return;

  const storageKey=`thai-news-tour-${config.id}`;
  const reducedMotion=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let currentIndex=0;
  let previousFocus=null;

  const isComplete=()=>{
    try{return localStorage.getItem(storageKey)==='complete';}catch(error){return true;}
  };
  const rememberComplete=()=>{
    try{localStorage.setItem(storageKey,'complete');}catch(error){}
  };
  const clamp=(value,min,max)=>Math.min(Math.max(value,min),max);

  const place=()=>{
    if(layer.hidden)return;
    const target=steps[currentIndex]?.target;
    if(!(target instanceof HTMLElement))return;
    const rect=target.getBoundingClientRect();
    const padding=8;
    const left=clamp(rect.left-padding,6,window.innerWidth-12);
    const top=clamp(rect.top-padding,6,window.innerHeight-12);
    const width=Math.max(0,Math.min(rect.width+padding*2,window.innerWidth-left-6));
    const height=Math.max(0,Math.min(rect.height+padding*2,window.innerHeight-top-6));
    Object.assign(spotlight.style,{left:`${left}px`,top:`${top}px`,width:`${width}px`,height:`${height}px`});

    const panelWidth=dialog.offsetWidth;
    const panelHeight=dialog.offsetHeight;
    const targetCenter=left+width/2;
    const panelLeft=clamp(targetCenter-panelWidth/2,12,window.innerWidth-panelWidth-12);
    const roomBelow=window.innerHeight-(top+height);
    const panelTop=roomBelow>=panelHeight+18
      ? top+height+14
      : Math.max(12,top-panelHeight-14);
    Object.assign(dialog.style,{left:`${panelLeft}px`,top:`${panelTop}px`});
  };

  const showStep=index=>{
    currentIndex=clamp(index,0,steps.length-1);
    const step=steps[currentIndex];
    title.textContent=step.title||'';
    body.textContent=step.body||'';
    counter.textContent=String(config.counter||'{current}/{total}')
      .replace('{current}',String(currentIndex+1))
      .replace('{total}',String(steps.length));
    backButton.hidden=currentIndex===0;
    nextButton.textContent=currentIndex===steps.length-1
      ? nextButton.dataset.doneLabel||'Done'
      : nextButton.dataset.nextLabel||'Next';

    const rect=step.target.getBoundingClientRect();
    const outside=rect.top<16||rect.bottom>window.innerHeight-16;
    if(outside)step.target.scrollIntoView({block:'center',behavior:reducedMotion?'auto':'smooth'});
    window.setTimeout(()=>{
      place();
      dialog.focus({preventScroll:true});
    },outside&&!reducedMotion?320:0);
  };

  const finish=()=>{
    rememberComplete();
    layer.hidden=true;
    document.body.classList.remove('tour-open');
    if(previousFocus instanceof HTMLElement)previousFocus.focus({preventScroll:true});
  };

  const start=()=>{
    previousFocus=document.activeElement;
    currentIndex=0;
    layer.hidden=false;
    document.body.classList.add('tour-open');
    showStep(0);
  };

  skipButton.addEventListener('click',finish);
  backButton.addEventListener('click',()=>showStep(currentIndex-1));
  nextButton.addEventListener('click',()=>currentIndex===steps.length-1?finish():showStep(currentIndex+1));
  restartButton?.addEventListener('click',event=>{
    event.preventDefault();
    try{localStorage.removeItem(storageKey);}catch(error){}
    start();
  });
  if(restartButton)restartButton.hidden=false;

  layer.addEventListener('keydown',event=>{
    if(event.key==='Escape'){
      event.preventDefault();
      finish();
      return;
    }
    if(event.key!=='Tab')return;
    const focusable=[...dialog.querySelectorAll('button:not([hidden])')];
    if(!focusable.length)return;
    const first=focusable[0];
    const last=focusable[focusable.length-1];
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  });
  window.addEventListener('resize',place);
  window.addEventListener('scroll',place,true);
  window.addEventListener('load',place);

  if(!isComplete())window.requestAnimationFrame(start);
});
