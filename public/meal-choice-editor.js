(() => {
    const editor=document.getElementById('mealChoiceEditor'), list=document.getElementById('mealChoiceGroups'), shop=document.getElementById('shop_id');
    if(!editor)return;
    const id=()=>crypto.randomUUID();
    const esc=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const input=(key,value,type='text',extra='')=>`<input data-key="${key}" type="${type}" value="${esc(value)}" ${extra}>`;
    const option=o=>`<div class="meal-choice-option">${input('id',o.id||id(),'hidden')}<label>اسم الخيار${input('name',o.name,'text','required maxlength="100"')}</label><label>فرق السعر ₪${input('price',o.price??0,'number','min="0" max="100000" step="0.01" required')}</label><button type="button" data-remove-option aria-label="حذف الخيار">×</button></div>`;
    const group=g=>`<div class="meal-choice-group">${input('id',g.id||id(),'hidden')}<div class="meal-choice-settings"><label>اسم المجموعة${input('name',g.name,'text','required maxlength="100" placeholder="نوع النيجيري"')}</label><label>أقل عدد (0 اختياري)${input('min',g.min??1,'number','min="0" max="20" required')}</label><label>أكبر عدد${input('max',g.max??1,'number','min="1" max="20" required')}</label></div><div class="meal-choice-controls"><label>السماح بتكرار النوع<select data-key="allow_repeat"><option value="0" ${!Number(g.allow_repeat)?'selected':''}>لا</option><option value="1" ${Number(g.allow_repeat)?'selected':''}>نعم</option></select></label><button type="button" data-remove-group>حذف المجموعة</button></div><label>مصدر الخيارات<select data-key="source"><option value="manual">خيارات أكتبها بنفسي</option><option value="menu" ${g.source==='menu'?'selected':''}>كل أقسام المنيو (بدون الوجبة نفسها)</option></select></label><div class="meal-choice-options">${(g.options||[{},{ }]).map(option).join('')}</div><button type="button" data-add-option>+ إضافة خيار</button></div>`;
    const groups=()=>[...list.querySelectorAll('.meal-choice-group')];
    const sync=()=>{
        const active=(shop?.selectedOptions?.[0]?.dataset.catalogType||shop?.dataset.catalogType)==='restaurant';editor.hidden=!active;
        groups().forEach((g,i)=>{
            const fromMenu=g.querySelector('[data-key=source]').value==='menu';
            g.querySelector('.meal-choice-options').hidden=fromMenu;g.querySelector('[data-add-option]').hidden=fromMenu;
            g.querySelectorAll('[data-key]').forEach(field=>{
                const row=field.closest('.meal-choice-option');
                const j=row?[...g.querySelectorAll('.meal-choice-option')].indexOf(row):-1;
                field.name=`meal_choice_groups[${i}]${row?`[options][${j}]`:''}[${field.dataset.key}]`;
                field.disabled=!active || (!!row && g.querySelector('[data-key=source]').value==='menu');
            });
        });
    };
    list.addEventListener('change',sync);
    const changed=()=>{sync();editor.dispatchEvent(new Event('change',{bubbles:true}));};
    list.innerHTML=(window.OZMAN_MEAL_CHOICE_GROUPS||[]).map(group).join('');
    document.getElementById('addMealChoiceGroup').onclick=()=>{if(groups().length>=10)return;list.insertAdjacentHTML('beforeend',group({}));changed();};
    list.onclick=event=>{
        const g=event.target.closest('.meal-choice-group');if(!g)return;
        if(event.target.closest('[data-remove-group]'))g.remove();
        else if(event.target.closest('[data-add-option]')&&g.querySelectorAll('.meal-choice-option').length<30)g.querySelector('.meal-choice-options').insertAdjacentHTML('beforeend',option({}));
        else if(event.target.closest('[data-remove-option]')&&g.querySelectorAll('.meal-choice-option').length>1)event.target.closest('.meal-choice-option').remove();
        else return;
        changed();
    };
    window.restoreMealChoiceDraft=fields=>{
        if(!Object.hasOwn(fields,'meal_choice_editor_present'))return;
        const restored=[];
        for(const [key,value] of Object.entries(fields)){
            const m=key.match(/^meal_choice_groups\[(\d+)\](?:\[options\]\[(\d+)\])?\[(\w+)\]$/);if(!m)continue;
            const g=restored[m[1]]??={options:[]};
            if(m[2]!==undefined)(g.options[m[2]]??={})[m[3]]=value;else g[m[3]]=value;
        }
        list.innerHTML=restored.filter(Boolean).slice(0,10).map(g=>group({...g,options:g.options.filter(Boolean).slice(0,30)})).join('');sync();
    };
    editor.insertAdjacentHTML('beforeend','<input type="hidden" name="meal_choice_editor_present" value="1">');
    editor.querySelectorAll('[data-choice-preset]').forEach(button=>button.onclick=()=>{
        if(groups().length>=10)return;
        const key=button.dataset.choicePreset;
        const names=key==='sandwich'?['تونا حمرا','سالومون','تونا بيضا']:['سالومون','تونا حمرا','شرمس'];
        const count=Number(key);
        const preset=count?{name:'اختيار الرولات',source:'menu',min:count,max:count,allow_repeat:1,options:[]}:{name:key==='sandwich'?'نوع الساندويش':'نوع النيجيري',min:1,max:1,options:names.map(name=>({name,price:0}))};
        list.insertAdjacentHTML('beforeend',group(preset));changed();
    });
    shop?.addEventListener('change',sync);sync();
})();
