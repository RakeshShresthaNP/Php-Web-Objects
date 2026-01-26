/**
 # PWO Support - ULTIMATE SYNC MASTER 2026
 # Final Version: Auth, Sync, Files, Voice, and Smart Welcome
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
            .tick-read { color: #38bdf8; font-weight: bold; }
            #pwo-auth-overlay { backdrop-filter: blur(10px); z-index: 10000; }
            .auth-input { width: 100%; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 0.75rem; margin-bottom: 0.75rem; font-size: 0.875rem; outline: none; transition: all 0.2s; }
            .auth-input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
            .rec-active { animation: pulse-red 1s infinite; color: #ef4444 !important; }
            @keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            .audio-container { background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 8px; min-width: 200px; }
            .play-btn { background: #059669; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        </style>
    `);

    // --- 2. UI STRUCTURE ---
    document.body.insertAdjacentHTML('beforeend', `
        <div id="pwo-window" style="display:none;" class="fixed bottom-24 right-6 w-80 md:w-96 h-[550px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden font-sans">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <div id="pwo-dot" class="w-2 h-2 bg-slate-300 rounded-full"></div>
                    <div><h3 class="font-bold text-sm">Live Support</h3><p id="pwo-status" class="text-[9px] opacity-75">Ready</p></div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="pwo-logout" title="Sign Out" class="text-xs opacity-70 hover:opacity-100">🚪</button>
                    <button id="pwo-close" class="text-xl">&times;</button>
                </div>
            </div>

            <div id="pwo-auth-overlay" class="hidden absolute inset-0 bg-white/95 flex flex-col items-center justify-center p-8 text-center">
                <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-4 shadow-lg">🔒</div>
                <h3 class="font-bold text-slate-800 text-lg">Member Login</h3>
                <p class="text-[10px] text-slate-400 mb-6 uppercase tracking-widest">Access Support Sync</p>
                <input id="pwo-user" type="text" placeholder="Username" class="auth-input">
                <input id="pwo-pass" type="password" placeholder="Password" class="auth-input">
                <button id="pwo-do-login" class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold shadow-md hover:bg-emerald-700 transition-all">Unlock Chat</button>
            </div>

            <div id="pwo-rec-panel" class="hidden bg-slate-100 p-2 border-b flex items-center gap-3">
                <canvas id="pwo-waveform" class="w-24 h-8 bg-white rounded border"></canvas>
                <div class="flex-1 overflow-hidden">
                    <div id="pwo-transcript" class="text-[9px] text-emerald-700 italic truncate">Listening...</div>
                    <div id="pwo-timer" class="text-[10px] font-mono text-gray-700">00:00 / 02:00</div>
                </div>
            </div>

            <div id="pwo-progress-container" class="h-1 bg-emerald-900 hidden shrink-0"><div id="pwo-progress-bar" class="h-full bg-yellow-400 w-0 transition-all duration-300"></div></div>
            
            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3 relative scroll-smooth">
                <button id="pwo-scroll-btn" class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-emerald-500 text-white px-3 py-1 rounded-full shadow-lg text-xs hidden">New Messages ↓</button>
            </div>

            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-50 border-t flex justify-between items-center shrink-0">
                <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate mr-4"></span>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-black uppercase">Remove</button>
            </div>

            <div class="p-4 bg-white border-t flex gap-2 items-center shrink-0">
                <input type="file" id="pwo-file-input" class="hidden">
                <button id="pwo-attach" class="text-xl text-gray-400 hover:text-emerald-600">📎</button>
                <button id="pwo-mic" class="text-xl text-gray-400 hover:text-red-500">🎤</button>
                <input id="chat-in" type="text" placeholder="Type message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999]">💬</button>
    `);

    // --- 3. CORE VARIABLES & HELPERS ---
    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    let isSocketStarted = false, isRecording = false, pendingFile = null;
    let recognition = null, mediaRecorder = null, audioChunks = [], recSeconds = 0, recTimer = null;
    let audioCtx, analyser, dataArray;

    const chatBox = document.getElementById('chat-box'), chatIn = document.getElementById('chat-in');
    const canvas = document.getElementById('pwo-waveform'), ctx = canvas.getContext('2d');
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    window.copyText = (t) => { navigator.clipboard.writeText(t); alert("Copied!"); };
    window.toggleAudio = (id, btn) => {
        const a = document.getElementById(id);
        a.paused ? a.play() : a.pause();
        btn.innerText = a.paused ? '▶' : '⏸';
    };

    // --- 4. AUTH & SYNC ENGINE ---
    const showLogin = () => document.getElementById('pwo-auth-overlay').classList.remove('hidden');
    
    document.getElementById('pwo-do-login').onclick = async () => {
        const username = document.getElementById('pwo-user').value;
        const password = document.getElementById('pwo-pass').value;
        if (!username || !password) return;

        try {
            const resp = await fetch('api/auth/login', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({ username, password }) 
            });
            const res = await resp.json();
            const token = res.data?.accessToken || res.token;
            if (token) {
                localStorage.setItem('pwoToken', token);
                localStorage.setItem('pwoUserId', res.data?.user_id || res.user_id);
                location.reload(); 
            } else alert("Invalid Login");
        } catch (e) { alert("Auth Error"); }
    };

    document.getElementById('pwo-logout').onclick = () => {
        if(confirm("Sign out of support?")) {
            localStorage.removeItem('pwoToken');
            localStorage.removeItem('pwoUserId');
            location.reload();
        }
    };

    // --- 5. RENDER ENGINE (IDENTICAL TO YOUR OPTIMIZED SYNC VERSION) ---
	function render(data, type) {
	    if (!data || (data.id && document.getElementById(`msg-${data.id}`))) return;

	    const myId = localStorage.getItem('pwoUserId');
	    const isMe = type === 'me' || data.is_me === true || (data.sender_id && String(data.sender_id) === String(myId));
	    
	    const msg = document.createElement('div');
	    msg.id = `msg-${data.id || Date.now()}`;
	    msg.className = `p-3 rounded-2xl max-w-[85%] text-sm relative group transition-all duration-300 ${isMe ? 'bg-emerald-600 text-white self-end msg-me shadow-md' : 'bg-white border text-gray-800 self-start msg-remote shadow-sm'}`;
	    
	    let media = '';
	    if (data.file_path) {
	        const path = data.file_path.toLowerCase();
	        if (path.match(/\.(webm|mp3|ogg|wav|m4a)$/i)) {
	            const audioId = 'audio-' + Math.random().toString(36).substr(2, 9);
	            media = `<div class="audio-container mb-2 flex items-center gap-3"><button class="play-btn" onclick="toggleAudio('${audioId}', this)">▶</button><audio id="${audioId}" src="${data.file_path}"></audio><div class="text-[9px] font-bold">VOICE</div></div>`;
	        } else if (path.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
	            media = `<img src="${data.file_path}" class="rounded-lg mb-2 max-w-full cursor-pointer" onclick="window.open('${data.file_path}')">`;
	        } else {
	            media = `<a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] mb-2 truncate font-bold uppercase">📄 ${data.file_name || 'FILE'}</a>`;
	        }
	    }

	    const time = data.time || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
	    msg.innerHTML = `${media}<div class="break-words leading-relaxed">${data.message || ''}</div><div class="flex items-center justify-end text-[8px] opacity-70 mt-1">${time} <span class="status-ticks">${isMe ? '✓' : ''}</span></div>`;

	    chatBox.appendChild(msg);
	    chatBox.scrollTop = chatBox.scrollHeight;
	}

    // --- 6. SPEECH & MIC LOGIC ---
    if ('webkitSpeechRecognition' in window) {
        recognition = new webkitSpeechRecognition(); recognition.continuous = true; recognition.interimResults = true;
        recognition.onresult = (e) => { 
            let t = ''; for(let i=e.resultIndex; i<e.results.length; ++i) t += e.results[i][0].transcript;
            chatIn.value = t; document.getElementById('pwo-transcript').innerText = t;
        };
    }

    const micBtn = document.getElementById('pwo-mic');
    micBtn.onclick = async () => {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream); audioChunks = [];
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = () => {
                    const blob = new Blob(audioChunks, { type: 'audio/webm' });
                    const r = new FileReader(); r.onload = (e) => { 
                        pendingFile = { data: e.target.result, name: `voice_${Date.now()}.webm` }; 
                        document.getElementById('pwo-filename').innerText = "Voice Note Ready"; 
                        document.getElementById('pwo-preview').classList.remove('hidden'); 
                    };
                    r.readAsDataURL(blob); stream.getTracks().forEach(t => t.stop());
                };
                mediaRecorder.start(); recognition?.start(); isRecording = true;
                micBtn.classList.add('rec-active'); document.getElementById('pwo-rec-panel').classList.remove('hidden');
            } catch (e) { alert("Mic error"); }
        } else {
            mediaRecorder?.stop(); recognition?.stop(); isRecording = false;
            micBtn.classList.remove('rec-active'); document.getElementById('pwo-rec-panel').classList.add('hidden');
        }
    };

    // --- 7. WS SERVICE (STARTING LOGIC) ---
    const startService = () => {
        const token = localStorage.getItem('pwoToken');
        if (!token) return showLogin();
        if (isSocketStarted) return;
        ws.connect();
        isSocketStarted = true;

        window.addEventListener('ws_connected', () => {
            document.getElementById('pwo-dot').className = 'w-2 h-2 bg-emerald-400 rounded-full animate-pulse';
            ws.call('chat', 'history', { token: token }, getAuthHeaders());
        });

        window.addEventListener('ws_chat_history', e => {
            chatBox.innerHTML = '';
            const hist = e.detail.data || [];
            hist.forEach(m => render(m));
            if (hist.length === 0) {
                const h = new Date().getHours();
                const greet = h < 12 ? 'Good Morning' : h < 17 ? 'Good Afternoon' : 'Good Evening';
                render({ message: `${greet}! Welcome to our support. How can we help you?`, sender_id: 0 });
            }
        });

        // This listener is the key to Cross-Browser Sync
        window.addEventListener('ws_new_message', e => render(e.detail.data || e.detail));
        
        window.addEventListener('ws_error', (e) => {
            if (e.detail?.code === 401) { localStorage.removeItem('pwoToken'); showLogin(); }
        });
    };

    // --- 8. FILE CHUNKING & SEND ---
    const handleSend = async () => {
        const txt = chatIn.value.trim(), t = localStorage.getItem('pwoToken');
        if (!txt && !pendingFile) return;

        if (pendingFile) {
            document.getElementById('pwo-progress-container').classList.remove('hidden');
            const CHUNK = 32768, total = Math.ceil(pendingFile.data.length / CHUNK), fid = Date.now();
            for (let i = 0; i < total; i++) {
                ws.call('chat', 'uploadchunk', { file_id: fid, chunk: pendingFile.data.substring(i * CHUNK, (i + 1) * CHUNK), index: i, token: t }, getAuthHeaders());
                document.getElementById('pwo-progress-bar').style.width = ((i+1)/total)*100 + '%';
                await new Promise(r => setTimeout(r, 10));
            }
            ws.call('chat', 'send', { message: txt, file_id: fid, file_name: pendingFile.name, token: t }, getAuthHeaders());
        } else {
            ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
        }
        chatIn.value = ''; pendingFile = null; document.getElementById('pwo-preview').classList.add('hidden');
        setTimeout(() => document.getElementById('pwo-progress-container').classList.add('hidden'), 500);
    };

    // --- 9. BINDINGS ---
    document.getElementById('pwo-bubble').onclick = () => {
        const win = document.getElementById('pwo-window');
        win.style.display = win.style.display === 'none' ? 'flex' : 'none';
        if (win.style.display === 'flex') startService();
    };
    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = (e) => { if(e.key === 'Enter') handleSend(); };
    document.getElementById('pwo-attach').onclick = () => document.getElementById('pwo-file-input').click();
    document.getElementById('pwo-file-input').onchange = (e) => {
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
    document.getElementById('pwo-close').onclick = () => document.getElementById('pwo-window').style.display = 'none';
    document.getElementById('pwo-clear').onclick = () => { pendingFile = null; document.getElementById('pwo-preview').classList.add('hidden'); };
})();