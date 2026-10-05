(() => {
    const root=document.getElementById('mealChoices');if(!root)return;
    const lang=document.documentElement.lang;
    const copy=lang==='en'?{choose:'Choose',to:'to',each:'per meal',selected:'Selected',error:'Complete the meal choices before adding to your order.'}:lang==='he'?{choose:'בחרו',to:'עד',each:'לכל מנה',selected:'נבחרו',error:'השלימו את בחירת המנה לפני ההוספה להזמנה.'}:{choose:'اختار',to:'إلى',each:'لكل وجبة',selected:'تم اختيار',error:'كمّل اختيارات الوجبة حسب العدد المطلوب قبل إضافتها للسلة.'};
    let groups=[];
    const error=document.getElementById('mealChoicesError');
    function read(){
        return groups.map((g,i)=>({group_id:g.id,options:[...root.children[i].querySelectorAll('[data-option]')].map(field=>({option_id:field.dataset.option,qty:field.type==='number'?Number(field.value):Number(field.checked)})).filter(o=>o.qty>0)}));
    }
    function update(){
        const selections=read();
        selections.forEach((s,i)=>{
            const g=groups[i],count=s.options.reduce((n,o)=>n+o.qty,0),box=root.children[i];
            box.querySelector('[data-count]').textContent=`${copy.selected}: ${count} / ${g.max}`;
            box.querySelectorAll('input[type=checkbox]').forEach(f=>f.disabled=!f.checked&&count>=g.max);
            box.dataset.valid=String(count>=g.min&&count<=g.max&&s.options.every(o=>Number.isInteger(o.qty))&&[...box.querySelectorAll('input[type=number]')].every(f=>f.validity.valid));
        });
        error.hidden=true;
    }
    window.OzmanMealChoices={
        render(data){groups=data||[];root.replaceChildren();error.hidden=true;
            groups.forEach(g=>{
                const section=document.createElement('section');section.className='option-section meal-choice-section';
                const title=document.createElement('h4');title.textContent=g.name;
                const hint=document.createElement('p');hint.className='meal-choice-hint';hint.textContent=`${copy.choose} ${g.min===g.max?g.max:g.min+' '+copy.to+' '+g.max} · ${copy.each}`;
                const counter=document.createElement('small');counter.dataset.count='';counter.setAttribute('aria-live','polite');
                section.append(title,hint,counter);
                g.options.forEach(o=>{
                    const label=document.createElement('label');label.className='choice';
                    const caption=document.createElement('span');caption.textContent=o.name+(Number(o.price)>0?' +'+Number(o.price).toFixed(2)+' ₪':'');
                    const input=document.createElement('input');input.dataset.option=o.id;input.setAttribute('aria-label',o.name);
                    if(g.allow_repeat){input.type='number';input.min=0;input.max=g.max;input.step=1;input.value=0;input.className='field meal-choice-qty';}
                    else {input.type=g.max===1&&g.min>0?'radio':'checkbox';input.name='meal-choice-'+g.id;}
                    input.addEventListener('input',update);input.addEventListener('change',update);
                    label.append(caption,input);section.append(label);
                });root.append(section);
            });update();
        },
        selection(){update();const values=read();
            const invalid=[...root.children].find(section=>section.dataset.valid==='false');
            if(invalid){error.textContent=copy.error;error.hidden=false;invalid.scrollIntoView({block:'center',behavior:'smooth'});invalid.querySelector('input')?.focus();return null;}
            const summary=[];let extra=0;
            values.forEach((value,i)=>{const labels=[];value.options.forEach(s=>{const o=groups[i].options.find(o=>o.id===s.option_id);labels.push(`${s.qty}× ${o.name}`);extra+=s.qty*Number(o.price);});if(labels.length)summary.push(groups[i].name+': '+labels.join('، '));});
            return {values:values.filter(g=>g.options.length),summary:summary.join(' | '),extra};
        },
    };
})();
