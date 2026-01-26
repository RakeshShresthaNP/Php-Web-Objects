/**
 # PWO Support - UNIVERSAL STABLE VERSION
 # Fixes: Race conditions in file upload and WebSocket buffering for Chrome & Firefox.
 */
(async function() {
    const SOCKET_URL = window.location.protocol === 'https:' ? `wss://${window.location.hostname}/ws` : `ws://localhost:8080`;

    // --- 1. DEPENDENCIES & STYLES ---
    const loadScript = (src) => new Promise(r => {
        if (document.querySelector(`script[src="${src}"]`)) return r();
        const s = document.createElement('script'); s.src = src; s.onload = r; document.head.appendChild(s);
    });
    await Promise.all([loadScript('assets/public/tailwind.js'), loadScript('assets/public/wsclient.js')]);

    document.head.insertAdjacentHTML('beforeend', `
        <style>
            .msg-me { border-bottom-right-radius: 2px !important; }
            .msg-remote { border-bottom-left-radius: 2px !important; }
            .rec-active { animation: pulse-red 1s infinite; color: #ef4444 !important; }
            @keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            .audio-container { background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 8px; min-width: 200px; display: flex; align-items: center; gap: 8px; }
            .play-btn { background: #059669; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
            .loader { border: 2px solid #f3f3f3; border-top: 2px solid #10b981; border-radius: 50%; width: 12px; height: 12px; animation: spin 1s linear infinite; display: inline-block; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
    `);

    // --- 2. UI STRUCTURE ---
    document.body.insertAdjacentHTML('beforeend', `
        <div id="pwo-window" style="display:none;" class="fixed bottom-24 right-6 w-80 md:w-96 h-[550px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden font-sans">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <div id="pwo-dot" class="w-2 h-2 bg-slate-300 rounded-full"></div>
                    <div><h3 class="font-bold text-sm">Live Support</h3><p id="pwo-status" class="text-[9px] opacity-75">Secure Sync</p></div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="pwo-logout" class="text-xs opacity-70 hover:opacity-100 bg-transparent text-white border-none cursor-pointer">🚪</button>
                    <button id="pwo-close" class="text-xl bg-transparent text-white border-none cursor-pointer">&times;</button>
                </div>
            </div>

            <div id="pwo-auth-overlay" class="hidden absolute inset-0 bg-white/95 flex flex-col items-center justify-center p-8 text-center z-50">
                <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-4 shadow-lg">🔒</div>
                <h3 class="font-bold text-slate-800">Member Login</h3>
                <input id="pwo-user" type="text" placeholder="Username" class="w-full border p-3 rounded-xl mt-4 mb-2 outline-none text-sm">
                <input id="pwo-pass" type="password" placeholder="Password" class="w-full border p-3 rounded-xl mb-4 outline-none text-sm">
                <button id="pwo-do-login" class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold">Unlock Chat</button>
            </div>

            <div id="pwo-rec-panel" class="hidden bg-slate-50 p-2 border-b text-center"><span id="pwo-timer" class="text-[10px] font-mono text-emerald-600 font-bold">RECORDING...</span></div>
            <div id="pwo-progress-container" class="h-1 bg-emerald-900 hidden"><div id="pwo-progress-bar" class="h-full bg-yellow-400 w-0 transition-all"></div></div>
            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3 scroll-smooth"></div>

            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-50 border-t flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div id="pwo-file-loader" class="loader hidden"></div>
                    <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate mr-4"></span>
                </div>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-bold uppercase bg-transparent border-none cursor-pointer">Remove</button>
            </div>

            <div class="p-4 bg-white border-t flex gap-2 items-center">
                <input type="file" id="pwo-file-input" style="position:fixed; top:-100em; opacity:0;">
                <button id="pwo-attach" type="button" class="text-xl text-gray-400 bg-transparent border-none cursor-pointer">📎</button>
                <button id="pwo-mic" type="button" class="text-xl text-gray-400 bg-transparent border-none cursor-pointer">🎤</button>
                <input id="chat-in" type="text" placeholder="Type..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none">
                <button id="chat-send" type="button" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center cursor-pointer border-none">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] cursor-pointer border-none">💬</button>
    `);

    // --- 3. STATE ---
    const chatBox = document.getElementById('chat-box'), chatIn = document.getElementById('chat-in'), micBtn = document.getElementById('pwo-mic');
    let isSocketStarted = false, isRecording = false, pendingFile = null, mediaRecorder = null, audioChunks = [], recognition = null;
    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    // --- 4. RENDER ENGINE ---
    function render(data) {
        if (!data || (data.id && document.getElementById(`msg-${data.id}`))) return;
        const myId = localStorage.getItem('pwoUserId');
        const isMe = data.is_me || (data.sender_id && String(data.sender_id) === String(myId));
        const msg = document.createElement('div');
        msg.id = `msg-${data.id || Date.now()}`;
        msg.className = `p-3 rounded-2xl max-w-[85%] text-sm mb-2 shadow-sm ${isMe ? 'bg-emerald-600 text-white self-end msg-me' : 'bg-white border text-gray-800 self-start msg-remote'}`;
        
        let media = '';
        if (data.file_path) {
            const path = data.file_path.toLowerCase();
            if (path.match(/\.(webm|mp3|wav|ogg|m4a)$/)) {
                const aid = 'aud-' + Math.random().toString(36).substr(2, 5);
                media = `<div class="audio-container mb-2"><button class="play-btn" onclick="const a=document.getElementById('${aid}'); a.paused?a.play():a.pause(); this.innerText=a.paused?'▶':'⏸'">▶</button><audio id="${aid}" src="${data.file_path}"></audio><span class="text-[9px] font-bold uppercase">Voice Note</span></div>`;
            } else if (path.match(/\.(jpg|jpeg|png|webp|gif)$/)) {
                media = `<img src="${data.file_path}" class="rounded-lg mb-2 max-w-full cursor-pointer" onclick="window.open('${data.file_path}')">`;
            } else {
                media = `<a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] mb-2 truncate font-bold uppercase">📄 ${data.file_name || 'FILE'}</a>`;
            }
        }
        msg.innerHTML = `${media}<div>${data.message || ''}</div>`;
        chatBox.appendChild(msg);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // --- 5. CORE LOGIC ---
    const handleSend = async () => {
        if (isRecording) stopRecording();
        const txt = chatIn.value.trim(), t = localStorage.getItem('pwoToken');
        if (!txt && !pendingFile) return;

        if (pendingFile) {
            const prog = document.getElementById('pwo-progress-container');
            const bar = document.getElementById('pwo-progress-bar');
            prog.classList.remove('hidden');

            const data = pendingFile.data, CHUNK = 16384; // Smaller chunk for stability
            const total = Math.ceil(data.length / CHUNK), fid = Date.now();

            for (let i = 0; i < total; i++) {
                ws.call('chat', 'uploadchunk', { file_id: fid, chunk: data.substring(i * CHUNK, (i + 1) * CHUNK), index: i, token: t }, getAuthHeaders());
                bar.style.width = ((i + 1) / total) * 100 + '%';
                await new Promise(r => setTimeout(r, 10)); // Essential delay for Chrome/Firefox stability
            }
            ws.call('chat', 'send', { message: txt, file_id: fid, file_name: pendingFile.name, token: t }, getAuthHeaders());
        } else {
            ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
        }

        chatIn.value = ''; pendingFile = null; document.getElementById('pwo-preview').classList.add('hidden');
        setTimeout(() => prog.classList.add('hidden'), 500);
    };

    const stopRecording = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            isRecording = false;
            micBtn.classList.remove('rec-active');
            document.getElementById('pwo-rec-panel').classList.add('hidden');
        }
    };

    // --- 6. ATTACHMENT FIXES ---
    const fInput = document.getElementById('pwo-file-input');
    document.getElementById('pwo-attach').onclick = () => fInput.click();

    fInput.addEventListener('change', function(e) {
        const f = this.files[0];
        if (f) {
            const loader = document.getElementById('pwo-file-loader');
            const nameSpan = document.getElementById('pwo-filename');
            document.getElementById('pwo-preview').classList.remove('hidden');
            loader.classList.remove('hidden');
            nameSpan.innerText = "Loading...";

            const r = new FileReader();
            r.onload = (ev) => {
                pendingFile = { data: ev.target.result, name: f.name };
                nameSpan.innerText = "✓ " + f.name;
                loader.classList.add('hidden');
                fInput.value = ""; 
            };
            r.readAsDataURL(f);
        }
    });

    micBtn.onclick = async () => {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream); audioChunks = [];
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = () => {
                    const blob = new Blob(audioChunks, { type: 'audio/webm' });
                    const r = new FileReader();
                    r.onload = (e) => {
                        pendingFile = { data: e.target.result, name: `voice_${Date.now()}.webm` };
                        document.getElementById('pwo-filename').innerText = "✓ Voice Note Ready";
                        document.getElementById('pwo-preview').classList.remove('hidden');
                        stream.getTracks().forEach(t => t.stop());
                    };
                    r.readAsDataURL(blob);
                };
                mediaRecorder.start(); 
                isRecording = true;
                micBtn.classList.add('rec-active');
                document.getElementById('pwo-rec-panel').classList.remove('hidden');
            } catch (e) { alert("Mic access denied"); }
        } else { stopRecording(); }
    };

    // --- 7. BINDINGS & START ---
    document.getElementById('pwo-bubble').onclick = () => {
        const win = document.getElementById('pwo-window');
        win.style.display = win.style.display === 'none' ? 'flex' : 'none';
        if (win.style.display === 'flex') {
            const token = localStorage.getItem('pwoToken');
            if (!token) { document.getElementById('pwo-auth-overlay').classList.remove('hidden'); return; }
            if (!isSocketStarted) {
                ws.connect();
                isSocketStarted = true;
                window.addEventListener('ws_connected', () => ws.call('chat', 'history', { token: token }, getAuthHeaders()));
                window.addEventListener('ws_chat_history', e => {
                    chatBox.innerHTML = '';
                    (e.detail.data || []).forEach(m => render(m));
                    if (chatBox.children.length === 0) render({ message: "Hello! How can we help?", sender_id: 0 });
                });
                window.addEventListener('ws_new_message', e => render(e.detail.data || e.detail));
            }
        }
    };

    document.getElementById('pwo-do-login').onclick = async () => {
        const u = document.getElementById('pwo-user').value, p = document.getElementById('pwo-pass').value;
        const resp = await fetch('api/auth/login', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:u, password:p}) });
        const res = await resp.json();
        const token = res.data?.accessToken || res.token;
        if (token) {
            localStorage.setItem('pwoToken', token);
            localStorage.setItem('pwoUserId', res.data?.user_id || res.user_id);
            location.reload(); 
        } else alert("Invalid credentials");
    };

    document.getElementById('pwo-logout').onclick = () => { localStorage.clear(); location.reload(); };
    document.getElementById('pwo-close').onclick = () => document.getElementById('pwo-window').style.display = 'none';
    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = (e) => { if(e.key === 'Enter') handleSend(); };
    document.getElementById('pwo-clear').onclick = () => { pendingFile = null; document.getElementById('pwo-preview').classList.add('hidden'); };
})();