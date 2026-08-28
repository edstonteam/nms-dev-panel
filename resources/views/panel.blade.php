<aside id="nms-dev-panel" aria-label="Developer panel">
    @if ($jiraUrl)
        <a class="nms-dev-panel__tab" href="{{ $jiraUrl }}" target="_blank" rel="noopener noreferrer" title="{{ $branch }}">{{ $branchLabel }}</a>
    @else
        <span class="nms-dev-panel__tab" title="{{ $branch }}">{{ $branchLabel }}</span>
    @endif
    <div class="nms-dev-panel__content">
        <div class="nms-dev-panel__branch">
            <strong>Branch</strong>
            @if ($jiraUrl)
                <a href="{{ $jiraUrl }}" target="_blank" rel="noopener noreferrer" title="{{ $branch }}">{{ $branchLabel }}</a>
            @else
                <code>{{ $branchLabel }}</code>
            @endif
        </div>
        <button type="button" data-nms-action="email">Generate email</button>
        <button type="button" data-nms-action="clear">Clear storage &amp; cookies</button>
        <button type="button" data-nms-action="payments">Configure payments</button>
        <button type="button" data-nms-action="database">Upload dump</button>
        <input type="file" data-nms-database-dump accept=".sql,.sql.gz,application/sql,application/gzip" hidden>
        <output class="nms-dev-panel__status" aria-live="polite"></output>
    </div>
</aside>
<style>
#nms-dev-panel{position:fixed;right:0;bottom:24px;z-index:2147483647;display:flex;align-items:stretch;max-width:calc(100vw - 16px);font:13px/1.4 ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;color:#e5e7eb;transform:translateX(calc(100% - 42px));transition:transform .18s ease;filter:drop-shadow(0 8px 20px rgba(0,0,0,.3))}#nms-dev-panel:hover,#nms-dev-panel:focus-within{transform:translateX(0)}.nms-dev-panel__tab{display:flex;width:42px;align-items:center;justify-content:center;border-radius:8px 0 0 8px;background:#111827;color:#34d399;font-size:11px;font-weight:800;letter-spacing:.08em;text-decoration:none;writing-mode:vertical-rl}.nms-dev-panel__content{display:grid;gap:8px;width:240px;padding:12px;background:#111827}.nms-dev-panel__branch{display:grid;gap:2px}.nms-dev-panel__branch strong{color:#9ca3af;font-size:10px;text-transform:uppercase}.nms-dev-panel__branch code,.nms-dev-panel__branch a{overflow:hidden;color:#34d399;text-decoration:none;text-overflow:ellipsis;white-space:nowrap}#nms-dev-panel button{margin:0;border:1px solid #374151;border-radius:5px;padding:7px 9px;background:#1f2937;color:#f9fafb;font:inherit;text-align:left;cursor:pointer}#nms-dev-panel button:hover,#nms-dev-panel button:focus{border-color:#34d399;outline:none}.nms-dev-panel__status{min-height:18px;overflow-wrap:anywhere;color:#d1d5db;font-size:11px}
</style>
<script>
(function(){
    var panel=document.getElementById('nms-dev-panel');
    if(!panel){return;}
    var status=panel.querySelector('.nms-dev-panel__status');
    var databaseDump=panel.querySelector('[data-nms-database-dump]');
    var token=@json(csrf_token());
    function parseResponse(response){
        return response.json().catch(function(){return {};}).then(function(data){
            if(!response.ok){throw new Error(data.message||'Request failed ('+response.status+')');}
            return data;
        });
    }
    function post(url,data){
        status.textContent='Working...';
        return fetch(url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:data?JSON.stringify(data):null}).then(parseResponse);
    }
    function upload(url,data){
        status.textContent='Uploading and replacing database...';
        return fetch(url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':token},body:data}).then(parseResponse);
    }
    function copy(value){
        if(navigator.clipboard&&window.isSecureContext){return navigator.clipboard.writeText(value);}
        var input=document.createElement('textarea');
        input.value=value;input.style.position='fixed';input.style.opacity='0';document.body.appendChild(input);input.select();document.execCommand('copy');input.remove();
        return Promise.resolve();
    }
    function setNativeValue(field,value){
        var ownSetter=Object.getOwnPropertyDescriptor(field,'value');
        var prototypeSetter=Object.getOwnPropertyDescriptor(Object.getPrototypeOf(field),'value');
        var setter=prototypeSetter&&(!ownSetter||ownSetter.set!==prototypeSetter.set)?prototypeSetter.set:ownSetter&&ownSetter.set;
        if(setter){setter.call(field,value);}else{field.value=value;}
    }
    function fillEmailFields(value){
        document.querySelectorAll('[name="email"]').forEach(function(field){
            if(field.disabled||field.readOnly){return;}
            field.focus();
            setNativeValue(field,value);
            field.dispatchEvent(typeof InputEvent==='function'?new InputEvent('input',{bubbles:true,data:value,inputType:'insertText'}):new Event('input',{bubbles:true}));
            field.dispatchEvent(new Event('change',{bubbles:true}));
            field.blur();
        });
    }
    function cookieNames(){
        return document.cookie.split(';').map(function(cookie){return cookie.split('=')[0].trim();}).filter(Boolean);
    }
    function cookiePaths(){
        return location.pathname.split('/').map(function(part,index,parts){return parts.slice(0,index+1).join('/')||'/';}).filter(function(path,index,paths){return paths.indexOf(path)===index;});
    }
    function cookieDomains(){
        var parts=location.hostname.split('.'),domains=[''];
        while(parts.length>1){domains.push(parts.join('.'));parts.shift();}
        return domains;
    }
    function clearBrowserCookies(){
        cookieNames().forEach(function(name){cookiePaths().forEach(function(path){cookieDomains().forEach(function(domain){
            document.cookie=name+'=; Max-Age=0; path='+path+(domain?'; domain='+domain:'');
        });});});
    }
    function clearBrowserState(){
        clearBrowserCookies();
        try{localStorage.clear();sessionStorage.clear();}catch(error){}
    }
    panel.querySelector('[data-nms-action="email"]').addEventListener('click',function(){
        post(@json(route('nms-dev-panel.email'))).then(function(data){fillEmailFields(data.email);return copy(data.email).then(function(){status.textContent=data.email+' filled and copied';});}).catch(function(error){status.textContent=error.message;});
    });
    panel.querySelector('[data-nms-action="payments"]').addEventListener('click',function(){
        if(!window.confirm('Replace Stripe and Ecommpay settings for every domain?')){return;}
        post(@json(route('nms-dev-panel.payments.reconfigure'))).then(function(data){status.textContent='Configured '+data.configurations+' settings for '+data.domains+' domains';}).catch(function(error){status.textContent=error.message;});
    });
    panel.querySelector('[data-nms-action="database"]').addEventListener('click',function(){
        databaseDump.value='';databaseDump.click();
    });
    databaseDump.addEventListener('change',function(){
        var file=databaseDump.files&&databaseDump.files[0];
        if(!file||!window.confirm('This permanently deletes and replaces the current database with "'+file.name+'". Continue?')){return;}
        var data=new FormData();data.append('confirmation','REPLACE');data.append('dump',file);
        upload(@json(route('nms-dev-panel.database.replace')),data).then(function(){clearBrowserState();status.textContent='Database replaced and payments configured';window.location.reload();}).catch(function(error){status.textContent=error.message;});
    });
    panel.querySelector('[data-nms-action="clear"]').addEventListener('click',function(){
        post(@json(route('nms-dev-panel.cookies.clear')),{cookie_names:cookieNames(),cookie_paths:cookiePaths()}).then(function(){clearBrowserState();window.location.reload();}).catch(function(error){status.textContent=error.message;});
    });
})();
</script>
