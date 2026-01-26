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
                    <div id="pwo-dot" class="w-3 h-3 bg-red-500 rounded-full border border-white/20 shadow-inner transition-colors duration-500"></div>
                    <div>
                        <h3 class="font-bold text-sm">Live Support</h3>
                        <p id="pwo-status" class="text-[9px] opacity-75 uppercase font-bold tracking-widest">Disconnected</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="pwo-logout" class="text-xs opacity-70 hover:opacity-100 bg-transparent text-white border-none cursor-pointer">🚪</button>
                    <button id="pwo-close" class="text-xl bg-transparent text-white border-none cursor-pointer">&times;</button>
                </div>
            </div>

            <div id="pwo-auth-overlay" class="hidden absolute inset-0 bg-white/95 flex flex-col items-center justify-center p-8 text-center z-50">
                <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mb-4 shadow-lg text-xl">🔒</div>
                <h3 class="font-bold text-slate-800">Member Login</h3>
                <input id="pwo-user" type="text" placeholder="Username" class="w-full border p-3 rounded-xl mt-4 mb-2 outline-none text-sm">
                <input id="pwo-pass" type="password" placeholder="Password" class="w-full border p-3 rounded-xl mb-4 outline-none text-sm">
                <button id="pwo-do-login" class="w-full bg-emerald-600 text-white py-3 rounded-xl font-bold">Unlock Chat</button>
            </div>

            <div id="pwo-rec-panel" class="hidden bg-emerald-50 p-2 border-b text-center border-emerald-100">
                <span id="pwo-timer" class="text-[10px] font-mono text-emerald-600 font-bold animate-pulse">● RECORDING & TRANSCRIBING...</span>
            </div>
            <div id="pwo-progress-container" class="h-1 bg-emerald-900/10 hidden">
                <div id="pwo-progress-bar" class="h-full bg-emerald-500 w-0 transition-all duration-300"></div>
            </div>

            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3 scroll-smooth"></div>

            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-100/50 border-t flex justify-between items-center border-emerald-200">
                <div class="flex items-center gap-2 truncate">
                    <div id="pwo-file-loader" class="loader hidden"></div>
                    <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate"></span>
                </div>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-bold uppercase bg-transparent border-none cursor-pointer ml-2">Remove</button>
            </div>

            <div class="p-4 bg-white border-t flex gap-2 items-center">
                <input type="file" id="pwo-file-input" class="hidden">
                <button id="pwo-attach" type="button" class="text-xl text-gray-400 hover:text-emerald-600 cursor-pointer bg-transparent border-none">📎</button>
                <button id="pwo-mic" type="button" class="text-xl text-gray-400 hover:text-red-500 cursor-pointer bg-transparent border-none">🎤</button>
                <input id="chat-in" type="text" placeholder="Type or use Mic..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500/20">
                <button id="chat-send" type="button" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center cursor-pointer border-none shadow-md hover:bg-emerald-700 transition-colors">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] cursor-pointer border-none hover:scale-110 transition-transform">💬</button>
    `);
		
    // --- 3. STATE ---
    const chatBox = document.getElementById('chat-box'), chatIn = document.getElementById('chat-in'), micBtn = document.getElementById('pwo-mic');
    let isReadingFile = false;
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
	            // HINT: The text (data.message) will now appear BELOW this audio player
	            media = `<div class="audio-container mb-2">
	                        <button class="play-btn" onclick="const a=document.getElementById('${aid}'); a.paused?a.play():a.pause(); this.innerText=a.paused?'▶':'⏸'">▶</button>
	                        <audio id="${aid}" src="${data.file_path}"></audio>
	                        <span class="text-[9px] font-bold uppercase">Voice Note</span>
	                     </div>`;
	        } else if (path.match(/\.(jpg|jpeg|png|webp|gif)$/)) {
	            media = `<img src="${data.file_path}" class="rounded-lg mb-2 max-w-full cursor-pointer" onclick="window.open('${data.file_path}')">`;
	        } else {
	            media = `<a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] mb-2 truncate font-bold uppercase">📄 ${data.file_name || 'FILE'}</a>`;
	        }
	    }

	    // This combines the Media and the Text in one bubble
	    msg.innerHTML = `${media}<div class="break-words">${data.message || ''}</div>`;
	    chatBox.appendChild(msg);
	    chatBox.scrollTop = chatBox.scrollHeight;
	}
	
    // --- 5. CORE LOGIC ---
	const handleSend = async () => {
        // 1. If still recording, stop it first
        if (isRecording) stopRecording();

        // 2. STABILITY GUARD: Wait for browser to finish reading the file/voice blob
        // This prevents the "pendingFile is null" error on both browsers
        let waitCount = 0;
        while (isReadingFile && waitCount < 20) {
            await new Promise(r => setTimeout(r, 100));
            waitCount++;
        }

        const txt = chatIn.value.trim(), t = localStorage.getItem('pwoToken');
        
        // If there is absolutely nothing to send, exit
        if (!txt && !pendingFile) return;

        // Define UI elements for progress tracking
        const prog = document.getElementById('pwo-progress-container');
        const bar = document.getElementById('pwo-progress-bar');

        if (pendingFile) {
            // --- ATTACHMENT MODE (FILE OR VOICE) ---
            if (prog) prog.classList.remove('hidden');

            const data = pendingFile.data;
            const CHUNK = 16384; // 16KB for maximum browser compatibility
            const total = Math.ceil(data.length / CHUNK);
            const fid = Date.now();

            // Upload chunks one by one
            for (let i = 0; i < total; i++) {
                ws.call('chat', 'uploadchunk', { 
                    file_id: fid, 
                    chunk: data.substring(i * CHUNK, (i + 1) * CHUNK), 
                    index: i, 
                    token: t 
                }, getAuthHeaders());

                if (bar) bar.style.width = ((i + 1) / total) * 100 + '%';
                
                // Essential delay to prevent WebSocket flooding/disconnects
                await new Promise(r => setTimeout(r, 15)); 
            }

            // FINAL CALL: Sends the TEXT (Transcription) + VOICE/FILE Link together
            ws.call('chat', 'send', { 
                message: txt, 
                file_id: fid, 
                file_name: pendingFile.name, 
                token: t 
            }, getAuthHeaders());

        } else {
            // --- TEXT ONLY MODE ---
            ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
        }

        // 3. CLEANUP & RESET
        chatIn.value = '';
        pendingFile = null;
        isReadingFile = false; // Reset the guard

        // Hide UI elements
        const preview = document.getElementById('pwo-preview');
        if (preview) preview.classList.add('hidden');
        
        setTimeout(() => {
            if (prog) {
                prog.classList.add('hidden');
                if (bar) bar.style.width = '0%';
            }
        }, 500);
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
	        isReadingFile = true; // <--- ADD THIS
	        // ... (your existing UI code)
	        const r = new FileReader();
	        r.onload = (ev) => {
	            pendingFile = { data: ev.target.result, name: f.name };
	            isReadingFile = false; // <--- ADD THIS
	            fInput.value = ""; 
	        };
	        r.readAsDataURL(f);
	    }
	});
	
	// --- MICROPHONE & TRANSCRIPTION LOGIC ---
    
    // Initialize Chrome Speech Engine
    if ('webkitSpeechRecognition' in window) {
        recognition = new webkitSpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.onresult = (event) => {
            let transcript = "";
            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) transcript += event.results[i][0].transcript;
            }
            if(transcript) chatIn.value = transcript.trim(); 
        };
    }

    micBtn.onclick = async () => {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];

                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);

                mediaRecorder.onstop = () => {
                    isReadingFile = true; // GUARD: Block send until ready
                    const blob = new Blob(audioChunks, { type: 'audio/webm' });
                    const r = new FileReader();
                    
                    r.onload = (e) => {
                        pendingFile = { 
                            data: e.target.result, 
                            name: `voice_${Date.now()}.webm` 
                        };
                        document.getElementById('pwo-filename').innerText = "✓ Voice Recorded";
                        document.getElementById('pwo-preview').classList.remove('hidden');
                        isReadingFile = false; // GUARD: Release
                        stream.getTracks().forEach(t => t.stop());
                    };
                    r.readAsDataURL(blob);
                };

                // START RECORDING & TRANSCRIBE
                mediaRecorder.start();
                if (recognition) recognition.start();
                
                isRecording = true;
                micBtn.classList.add('rec-active');
                document.getElementById('pwo-rec-panel').classList.remove('hidden');
            } catch (e) { 
                alert("Microphone access is required for voice messages."); 
            }
        } else {
            // STOP RECORDING & TRANSCRIBE
            if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
            if (recognition) recognition.stop();
            
            isRecording = false;
            micBtn.classList.remove('rec-active');
            document.getElementById('pwo-rec-panel').classList.add('hidden');
        }
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

	// --- CONNECTION STATUS HANDLERS ---
	window.addEventListener('ws_connected', () => {
	    const dot = document.getElementById('pwo-dot');
	    const status = document.getElementById('pwo-status');
	    if(dot) dot.style.backgroundColor = '#22c55e'; // Emerald Green
	    if(status) status.innerText = 'Connected';
	});

	window.addEventListener('ws_disconnected', () => {
	    const dot = document.getElementById('pwo-dot');
	    const status = document.getElementById('pwo-status');
	    if(dot) dot.style.backgroundColor = '#ef4444'; // Red
	    if(status) status.innerText = 'Offline';
	});
		
	document.getElementById('pwo-clear').onclick = () => { 
	    pendingFile = null; 
	    isReadingFile = false; // HINT: Reset the guard so text messages can still send
	    document.getElementById('pwo-preview').classList.add('hidden'); 
	};
})();