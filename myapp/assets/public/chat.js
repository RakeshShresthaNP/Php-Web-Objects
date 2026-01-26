/**
 # PWO Support - Master Script
 # Optimized: Lazy Socket Connection, Chrome Sequential Uploads, & History
 */

(async function() {
    const SOCKET_URL = window.location.protocol === 'https:' 
        ? `wss://${window.location.hostname}/ws` 
        : `ws://localhost:8080`;

    // --- 1. DEPENDENCIES ---
    const loadScript = (src) => new Promise(r => {
        if (document.querySelector(`script[src="${src}"]`)) return r();
        const s = document.createElement('script'); s.src = src; s.onload = r; document.head.appendChild(s);
    });

    await Promise.all([loadScript('assets/public/tailwind.js'), loadScript('assets/public/wsclient.js')]);

    // --- 2. UI STRUCTURE ---
    document.body.insertAdjacentHTML('beforeend', `
        <div id="pwo-window" style="display:none;" class="fixed bottom-24 right-6 w-80 md:w-96 h-[500px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <div id="pwo-dot" class="w-2 h-2 bg-slate-300 rounded-full"></div>
                    <div><h3 class="font-bold text-sm">Live Support</h3><p id="pwo-status" class="text-[10px] opacity-75">Connecting...</p></div>
                </div>
                <button id="pwo-close" class="text-xl">&times;</button>
            </div>
            <div id="pwo-login-panel" class="hidden absolute inset-0 bg-white z-50 flex flex-col items-center justify-center p-6 text-center">
                <div class="text-4xl mb-4">🔐</div>
                <h3 class="font-bold text-gray-800">Session Expired</h3>
                <button onclick="window.location.reload()" class="mt-4 bg-emerald-600 text-white px-6 py-2 rounded-full text-sm font-bold">Login Again</button>
            </div>
            <div id="pwo-progress-container" class="h-1 bg-emerald-900 hidden shrink-0">
                <div id="pwo-progress-bar" class="h-full bg-yellow-400 w-0 transition-all"></div>
            </div>
            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3">
                <div id="chat-loading" class="text-center text-gray-400 text-[10px] mt-20">Initializing Secure Connection...</div>
            </div>
            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-50 border-t flex justify-between items-center shrink-0">
                <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate"></span>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-black">REMOVE</button>
            </div>
            <div class="p-4 bg-white border-t flex gap-2 items-center shrink-0">
                <input type="file" id="pwo-file-input" class="hidden">
                <button id="pwo-attach" class="text-xl text-gray-400 hover:text-emerald-600">📎</button>
                <input id="chat-in" type="text" placeholder="Type message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999]">💬</button>
    `);

    // --- 3. LOGIC & WS ---
    const ws = new WSClient(SOCKET_URL, 'pwoToken'); 
    let isSocketStarted = false; // Flag to prevent multiple connections
    
    const chatBox = document.getElementById('chat-box');
    const chatIn = document.getElementById('chat-in');
    const progBar = document.getElementById('pwo-progress-bar');
    const progCont = document.getElementById('pwo-progress-container');
    const fileInput = document.getElementById('pwo-file-input');
    const loginPanel = document.getElementById('pwo-login-panel');
    
    let pendingFile = null;
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    function render(data, type) {
        if (!data) return;
        const isMe = type === 'me';
        const msg = document.createElement('div');
        msg.className = `p-3 rounded-2xl max-w-[85%] text-sm ${isMe ? 'bg-emerald-600 text-white self-end' : 'bg-white border text-gray-800 self-start'}`;
        
        let media = '';
        if (data.file_path) {
            const isImg = data.file_path.match(/\.(jpg|jpeg|png|webp|gif)$/i);
            media = isImg 
                ? `<img src="${data.file_path}" class="rounded-lg mb-2 max-w-full h-auto">` 
                : `<a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] mb-2 font-bold italic truncate">📄 ${data.file_name}</a>`;
        }
        
        msg.innerHTML = `${media}<div>${data.message || ''}</div><div class="text-[8px] opacity-50 mt-1 text-right">${data.time || ''}</div>`;
        chatBox.appendChild(msg);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    const startChatService = () => {
        if (isSocketStarted) return; // Don't connect twice
        
        ws.connect();
        isSocketStarted = true;
        
        // Setup listeners only once
        window.addEventListener('ws_connected', () => {
            document.getElementById('pwo-status').innerText = 'Online';
            document.getElementById('pwo-dot').className = 'w-2 h-2 bg-emerald-400 rounded-full animate-pulse';
            // Auto-load history as soon as connection is live
            ws.call('chat', 'history', { token: localStorage.getItem('pwoToken') }, getAuthHeaders());
        });

        window.addEventListener('ws_error', e => {
            if (e.detail?.code === 401) loginPanel.classList.remove('hidden');
            document.getElementById('pwo-status').innerText = 'Offline';
            document.getElementById('pwo-dot').className = 'w-2 h-2 bg-red-500 rounded-full';
        });

        window.addEventListener('ws_chat_history', e => {
            const loader = document.getElementById('chat-loading');
            if (loader) loader.remove();
            (e.detail.data || []).forEach(item => render(item, item.is_me ? 'me' : 'remote'));
        });

        window.addEventListener('ws_chat_confirmation', e => render(e.detail.data || e.detail, 'me'));
        window.addEventListener('ws_new_message', e => render(e.detail.data || e.detail, 'remote'));
    };

    const handleSend = async () => {
        const txt = chatIn.value.trim();
        const t = localStorage.getItem('pwoToken');
        if (!txt && !pendingFile) return;

        if (pendingFile) {
            progCont.classList.remove('hidden');
            const CHUNK_SIZE = 32768; 
            const totalChunks = Math.ceil(pendingFile.data.length / CHUNK_SIZE);
            const fid = Date.now();

            for (let i = 0; i < totalChunks; i++) {
                const chunk = pendingFile.data.substring(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                ws.call('chat', 'uploadchunk', { file_id: fid, chunk: chunk, index: i, token: t }, getAuthHeaders());
                progBar.style.width = `${Math.round(((i + 1) / totalChunks) * 100)}%`;
                
                // Chrome Fix: Wait between chunks
                await new Promise(r => setTimeout(r, 20));
            }
            await new Promise(r => setTimeout(r, 50));
            ws.call('chat', 'send', { message: txt, file_id: fid, file_name: pendingFile.name, token: t }, getAuthHeaders());
        } else {
            ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
        }

        chatIn.value = ''; pendingFile = null;
        document.getElementById('pwo-preview').classList.add('hidden');
        setTimeout(() => { progCont.classList.add('hidden'); progBar.style.width = '0%'; }, 1000);
    };

    // --- 4. UI EVENTS ---
    document.getElementById('pwo-bubble').onclick = () => {
        const win = document.getElementById('pwo-window');
        const isCurrentlyHidden = win.style.display === 'none';
        
        if (isCurrentlyHidden) {
            win.style.display = 'flex';
            startChatService(); // <--- Connect only when opened
        } else {
            win.style.display = 'none';
        }
    };

    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = e => { if (e.key === 'Enter') handleSend(); };
    document.getElementById('pwo-attach').onclick = () => fileInput.click();
    document.getElementById('pwo-close').onclick = () => document.getElementById('pwo-window').style.display = 'none';
    document.getElementById('pwo-clear').onclick = () => {
        pendingFile = null;
        document.getElementById('pwo-preview').classList.add('hidden');
    };

    fileInput.onchange = (e) => {
        const f = e.target.files[0];
        if (f) {
            const r = new FileReader();
            r.onload = (ev) => {
                pendingFile = { data: ev.target.result, name: f.name };
                document.getElementById('pwo-filename').innerText = f.name;
                document.getElementById('pwo-preview').classList.remove('hidden');
            };
            r.readAsDataURL(f);
        }
    };
})();