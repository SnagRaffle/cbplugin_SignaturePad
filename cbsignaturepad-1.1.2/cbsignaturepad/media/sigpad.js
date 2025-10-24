(function(){
  function init(root){
    const canvas = root.querySelector('.cb-sigpad-canvas');
    const clearBtn = root.querySelector('.cb-sigpad-clear');
    const saveBtn = root.querySelector('.cb-sigpad-save');
    const delBtn = root.querySelector('.cb-sigpad-delete');
    const statusEl = root.querySelector('.cb-sigpad-status');
    const fieldName = root.getAttribute('data-field');
    const penColor = root.getAttribute('data-color') || '#000';
    const penWidth = parseFloat(root.getAttribute('data-width') || '2');
    const bgColor = root.getAttribute('data-bg') || '#fff';
    const ctx = canvas.getContext('2d');
    let drawing=false,last={x:0,y:0},paths=[],cur=null;

    function cssH(){return parseInt(getComputedStyle(canvas).height,10)||200;}
    function resize(){
      const r=Math.max(window.devicePixelRatio||1,1);
      const w=canvas.clientWidth,h=cssH();
      canvas.width=Math.round(w*r);canvas.height=Math.round(h*r);
      ctx.setTransform(r,0,0,r,0,0);
      ctx.lineCap='round';ctx.lineJoin='round';ctx.lineWidth=penWidth;ctx.strokeStyle=penColor;
      redraw();
    }
    function pos(e){const b=canvas.getBoundingClientRect(); if(e.touches&&e.touches.length){return{x:e.touches[0].clientX-b.left,y:e.touches[0].clientY-b.top};} return{x:e.clientX-b.left,y:e.clientY-b.top};}
    function start(e){e.preventDefault();drawing=true;cur=[];const p=pos(e);last=p;cur.push(p);}
    function move(e){if(!drawing)return;e.preventDefault();const p=pos(e);ctx.beginPath();ctx.moveTo(last.x,last.y);ctx.lineTo(p.x,p.y);ctx.stroke();last=p;cur.push(p);}
    function end(){if(!drawing)return;drawing=false;if(cur&&cur.length){paths.push(cur);cur=null;}}
    function clear(){ctx.clearRect(0,0,canvas.width,canvas.height);paths.length=0;if(statusEl)statusEl.textContent='';}

    function redraw(){
      ctx.clearRect(0,0,canvas.width,canvas.height);
      // paint background
      ctx.save();
      ctx.globalCompositeOperation='destination-over';
      ctx.fillStyle=bgColor; ctx.fillRect(0,0,canvas.width,canvas.height);
      ctx.restore();
      ctx.beginPath();
      for (const path of paths){ if(!path.length) continue; ctx.moveTo(path[0].x,path[0].y); for(let i=1;i<path.length;i++){ ctx.lineTo(path[i].x,path[i].y); } }
      ctx.stroke();
    }

    canvas.addEventListener('mousedown',start);canvas.addEventListener('mousemove',move);document.addEventListener('mouseup',end);
    canvas.addEventListener('touchstart',start,{passive:false});canvas.addEventListener('touchmove',move,{passive:false});document.addEventListener('touchend',end);
    clearBtn && clearBtn.addEventListener('click',clear);
    resize(); if('ResizeObserver'in window)new ResizeObserver(resize).observe(canvas.parentElement); else window.addEventListener('resize',resize);

    function postJSON(url, body){ return fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(body)}).then(r=>r.json()); }
    const saveURL = 'index.php?option=com_comprofiler&task=pluginclass&plugin=cbsignaturepad&func=save&no_html=1';
    const delURL  = 'index.php?option=com_comprofiler&task=pluginclass&plugin=cbsignaturepad&func=delete&no_html=1';

    saveBtn && saveBtn.addEventListener('click', function(){
      if(paths.length===0){ statusEl && (statusEl.textContent='Please sign first.'); return; }
      statusEl && (statusEl.textContent='Saving...');
      const tmp=document.createElement('canvas'); tmp.width=canvas.clientWidth; tmp.height=cssH();
      const t=tmp.getContext('2d');
      t.fillStyle=bgColor; t.fillRect(0,0,tmp.width,tmp.height);
      t.lineCap='round';t.lineJoin='round';t.lineWidth=penWidth;t.strokeStyle=penColor;
      t.beginPath(); for(const path of paths){ if(!path.length) continue; t.moveTo(path[0].x,path[0].y); for(let i=1;i<path.length;i++){ t.lineTo(path[i].x,path[i].y); } } t.stroke();
      const base64 = tmp.toDataURL('image/png').split(',')[1];
      postJSON(saveURL,{field:fieldName,image:base64}).then(res=>{
        if(!(res&&res.success)) throw new Error(res&&res.message||'Save failed');
        const input = root.querySelector('input[name="'+fieldName+'"]'); if(input) input.value = res.file;
        statusEl && (statusEl.textContent='Saved.');
      }).catch(e=>{ statusEl && (statusEl.textContent='Save failed.'); console.error(e); });
    });

    delBtn && delBtn.addEventListener('click', function(){
      if(!confirm('Delete this signature?')) return;
      statusEl && (statusEl.textContent='Deleting...');
      postJSON(delURL,{field:fieldName}).then(res=>{
        if(!(res&&res.success)) throw new Error(res&&res.message||'Delete failed');
        clear();
        const input = root.querySelector('input[name="'+fieldName+'"]'); if(input) input.value='';
        const img = root.querySelector('.cb-sigpad-preview img'); if(img) img.remove();
        statusEl && (statusEl.textContent='Deleted.');
      }).catch(e=>{ statusEl && (statusEl.textContent='Delete failed.'); console.error(e); });
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.cb-sigpad').forEach(init);
  });
})();