@props(['leadId'])

<div x-data="copilotPanel({{ $leadId }})" class="bg-white rounded-lg shadow p-4 space-y-3">
    <div class="flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">AI Co-Pilot</h3>
        <span x-text="meta ? Math.round((meta.confidence||0)*100)+'% conf.' : ''"
              class="text-xs text-gray-500"></span>
    </div>

    <p class="text-sm text-gray-700" x-text="meta?.summary || 'Analyzing…'"></p>

    <div class="flex flex-wrap gap-2">
        <template x-for="btn in (meta?.actions || [])" :key="btn.action">
            <button
                class="px-3 py-1.5 rounded border text-sm hover:bg-gray-50"
                x-text="btn.label"
                @click="run(btn)"></button>
        </template>
    </div>

    <div x-show="suggested" class="pt-2">
        <label class="text-xs text-gray-500">Suggested reply</label>
        <textarea rows="3" class="w-full border rounded p-2 text-sm" x-model="suggested"></textarea>
        <div class="mt-2 flex gap-2">
            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm" @click="send()">Send</button>
            <button class="px-3 py-1.5 border rounded text-sm" @click="suggested=''">Clear</button>
        </div>
    </div>
</div>

<script>
function copilotPanel(leadId){
    return {
        meta:null, suggested:'',
        async load(){ const r = await fetch(`/admin/leads/${leadId}/copilot/meta`); this.meta = await r.json(); },
        async run(btn){
            const a = btn.action;
            if(a==='suggest_reply'){
                const r = await fetch(`/admin/leads/${leadId}/copilot/suggest-reply`);
                const j = await r.json(); this.suggested = j.reply || '';
            } else if(a==='quick_booking'){
                const r = await fetch(`/admin/leads/${leadId}/copilot/quick-booking`, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}});
                await r.json(); await this.load();
                alert('Quick booking created.');
            } else if(a==='schedule_followup'){
                const r = await fetch(`/admin/leads/${leadId}/copilot/followup`, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}});
                await r.json(); alert('Follow-up scheduled.');
            } else if(a==='send_template'){
                const r = await fetch(`/admin/leads/${leadId}/copilot/send-template`, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content, 'Content-Type':'application/json'}, body:JSON.stringify(btn.payload||{})});
                await r.json(); alert('Template sent.');
            }
        },
        async send(){
            if(!this.suggested) return;
            // Send via unified inbox endpoint (ChatController@send is per conversation; here we log direct)
            await fetch(`/admin/leads/${leadId}/copilot/send-template`, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content, 'Content-Type':'application/json'}, body:JSON.stringify({template:'__free_text__', body:this.suggested})});
            this.suggested=''; alert('Reply sent.');
        },
        init(){ this.load(); }
    }
}
</script>
