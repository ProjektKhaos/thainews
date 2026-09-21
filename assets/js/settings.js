// Thai News settings — 2026-09-20
'use strict';
document.addEventListener('DOMContentLoaded',()=>{
 const list=document.getElementById('source-sort'); if(!list)return;
 if(window.Sortable){new window.Sortable(list,{animation:130,handle:'.drag-handle',forceFallback:true,fallbackOnBody:true,swapThreshold:.65});}
 const move=(btn,dir)=>{const li=btn.closest('li');if(!li)return;const other=dir<0?li.previousElementSibling:li.nextElementSibling;if(!other)return;dir<0?list.insertBefore(li,other):list.insertBefore(other,li);btn.focus();};
 list.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;if(b.classList.contains('move-up')){e.preventDefault();move(b,-1);}if(b.classList.contains('move-down')){e.preventDefault();move(b,1);}});
});
