/**
 # PWO Support - ULTIMATE ENTERPRISE VERSION
 # FIXED: Chrome File Attachment + Removed Extra Boxes
 */
(async function() {
    const SOCKET_URL = window.location.protocol === 'https:' ? `wss://${window.location.hostname}/ws` : `ws://localhost:8080`;
    const NOTIFY_SOUND = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');
    
    let originalTitle = document.title;
    let flashInterval = null;

    const startTabFlash = () => {
        if (flashInterval || !document.hidden) return;
        flashInterval = setInterval(() => {
            document.title = document.title === originalTitle ? "(1) New Message!" : originalTitle;
        }, 1000);
    };

    const stopTabFlash = () => {
        clearInterval(flashInterval);
        flashInterval = null;
        document.title = originalTitle;
    };

    window.addEventListener('focus', stopTabFlash);

    // --- 1. DEPENDENCIES & STYLES ---
    const loadScript = (src) => new Promise(r => {
        if (document.querySelector(`script[src="${src}"]`)) return r();
        const s = document.createElement('script'); s.src = src; s.onload = r; document.head.appendChild(s);
    });
    await Promise.all([loadScript('assets/public/tailwind.js'), loadScript('assets/public/wsclient.js')]);

    document.head.insertAdjacentHTML('beforeend', `
        <style>
            /* CLEAN BUBBLES - NO EXTRA BOXES */
            .msg-me { border-bottom-right-radius: 2px !important; position: relative; }
            .msg-remote { border-bottom-left-radius: 2px !important; position: relative; }
            .rec-active { animation: pulse-red 1s infinite; color: #ef4444 !important; }
            @keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
            .audio-container { background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 8px; min-width: 200px; display: flex; align-items: center; gap: 8px; }
            .play-btn { background: #059669; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: none; }
            .typing-dot { width: 4px; height: 4px; background: #94a3b8; border-radius: 50%; animation: typing 1.4s infinite; }
            .typing-dot:nth-child(2) { animation-delay: 0.2s; }
            .typing-dot:nth-child(3) { animation-delay: 0.4s; }
            @keyframes typing { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
            .msg-status { font-size: 10px; margin-left: 5px; font-weight: bold; opacity: 0.8; vertical-align: bottom; }
            .msg-me .msg-status { color: rgba(255, 255, 255, 0.7); }
            .msg-status.is-read { color: #38bdf8 !important; }
        </style>
    `);

    // --- 2. UI STRUCTURE ---
    document.body.insertAdjacentHTML('beforeend', `
        <div id="pwo-window" style="display:none;" class="fixed bottom-24 right-6 w-80 md:w-96 h-[550px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden font-sans">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <div id="pwo-dot" class="w-3 h-3 bg-red-500 rounded-full border border-white/20 shadow-inner"></div>
                    <div>
                        <h3 class="font-bold text-sm">Live Support</h3>
                        <p id="pwo-status" class="text-[9px] opacity-75 uppercase font-bold tracking-widest">Disconnected</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button id="pwo-export" title="Export Chat" class="bg-transparent border-none text-white opacity-70 hover:opacity-100 cursor-pointer">📥</button>
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

            <div id="pwo-rec-panel" class="hidden bg-emerald-50 p-3 border-b border-emerald-100 flex flex-col items-center justify-center">
                <div class="w-full flex justify-center items-center h-12 mb-1 bg-white/50 rounded-lg border border-emerald-100/50">
                    <canvas id="pwo-waveform" width="300" height="48" class="w-full h-12"></canvas>
                </div>
                <span id="pwo-timer" class="text-[10px] font-mono text-emerald-600 font-bold">● 0:00</span>
            </div>

            <div id="pwo-progress-container" class="h-1 bg-emerald-900/10 hidden">
                <div id="pwo-progress-bar" class="h-full bg-emerald-500 w-0 transition-all duration-300"></div>
            </div>

            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3 scroll-smooth"></div>
            
            <div id="pwo-typing" class="hidden px-4 py-2 bg-slate-50 flex items-center gap-2 text-[10px] text-slate-400 font-bold italic">
                Support is typing <div class="flex gap-1"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>
            </div>

            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-100/50 border-t flex justify-between items-center border-emerald-200">
                <div class="flex items-center gap-2 truncate">
                    <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate"></span>
                </div>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-bold uppercase bg-transparent border-none cursor-pointer">Remove</button>
            </div>

            <div class="p-4 bg-white border-t flex gap-2 items-center">
                <input type="file" id="pwo-file-input" class="hidden">
                <button id="pwo-attach" type="button" class="text-xl text-gray-400 hover:text-emerald-600 cursor-pointer bg-transparent">📎</button>
                <button id="pwo-mic" type="button" class="text-xl text-gray-400 hover:text-red-500 cursor-pointer bg-transparent">🎤</button>
                <input id="chat-in" type="text" placeholder="Type..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none">
                <button id="chat-send" type="button" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center cursor-pointer border-none shadow-md">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] cursor-pointer border-none hover:scale-110">💬</button>
    `);

    // --- 3. STATE ---
    const chatBox = document.getElementById('chat-box'), chatIn = document.getElementById('chat-in'), micBtn = document.getElementById('pwo-mic');
    let isReadingFile = false, isSocketStarted = false, isRecording = false, pendingFile = null;
    let mediaRecorder = null, audioChunks = [], recognition = null, timerInterval = null, seconds = 0;
    let audioCtx, analyzer, source, animationId;
    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    // --- 4. RENDER ENGINE ---
    function parseMarkdown(t) {
        if (!t) return "";
        return t.replace(/\*\*(.*?)\*\*/g, '<b>$1</b>').replace(/\*(.*?)\*/g, '<i>$1</i>').replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="underline text-inherit font-bold">$1</a>');
    }

	function render(data, isNew = false, isPending = false) {
        // 1. EXIT: If there's no content and it's not a pending state, do nothing
        if (!data.message && !data.file_path && !isPending) return;

        const myId = localStorage.getItem('pwoUserId');
        const isMe = data.is_me || (data.sender_id && String(data.sender_id) === String(myId));
        
        // 2. DUPLICATION FIX: If a real message arrives, find and remove its 'Pending' version
        if (data.temp_id) {
            const existingPending = document.getElementById(`pending-${data.temp_id}`);
            if (existingPending) {
                existingPending.remove();
            }
        }

        // 3. ID CHECK: Prevent adding the same official message twice
        if (data.id && document.getElementById(`msg-${data.id}`)) return;

        // 4. NOTIFICATION: Play sound and flash tab for incoming messages
        if (isNew && !isMe) { 
            NOTIFY_SOUND.play().catch(e => { }); 
            startTabFlash(); 
        }
        
        const msg = document.createElement('div');
        // Unique ID prefix to differentiate between temporary and permanent bubbles
        msg.id = isPending ? `pending-${data.temp_id}` : `msg-${data.id || Date.now()}`;
        
        // STYLING: 'relative' is required for the absolute-positioned checkmarks
        msg.className = `rounded-2xl max-w-[85%] text-sm mb-2 shadow-sm relative ${isMe ? 'bg-emerald-600 text-white self-end msg-me' : 'bg-white border text-gray-800 self-start msg-remote'}`;
        
        // 5. MEDIA RENDERER
        let mediaHtml = '';
        if (data.file_path) {
            const path = data.file_path.toLowerCase();
            // Each media type gets its own padding wrapper to stay clean
            if (path.match(/\.(webm|mp3|wav|ogg|m4a)$/)) {
                const aid = 'aud-' + Math.random().toString(36).substr(2, 5);
                mediaHtml = `<div class="p-3 pb-1"><div class="audio-container"><button class="play-btn" onclick="const a=document.getElementById('${aid}'); a.paused?a.play():a.pause(); this.innerText=a.paused?'▶':'⏸'">▶</button><audio id="${aid}" src="${data.file_path}"></audio><span class="text-[9px] font-bold uppercase ml-2">Voice Note</span></div></div>`;
            } else if (path.match(/\.(jpg|jpeg|png|webp|gif)$/)) {
                mediaHtml = `<div class="p-1"><img src="${data.file_path}" class="rounded-lg max-w-full cursor-zoom-in" onclick="this.classList.toggle('fixed'); this.classList.toggle('inset-0'); this.classList.toggle('z-[10000]'); this.classList.toggle('m-auto'); this.classList.toggle('max-h-screen'); this.classList.toggle('bg-black/90')"></div>`;
            } else {
                mediaHtml = `<div class="p-3 pb-1"><a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] truncate font-bold uppercase">📄 ${data.file_name || 'FILE'}</a></div>`;
            }
        }

        // 6. TEXT RENDERER: Only creates a box if message exists (Fixes the "unnecessary bubble")
        // We use 'pb-4' to ensure there is space at the bottom for the status checkmarks
        const textHtml = data.message ? `<div class="p-3 pt-1 pb-4"><span class="break-words">${parseMarkdown(data.message)}</span></div>` : '<div class="pb-4"></div>';

        // 7. STATUS ICONS: Absolute positioning ensures they don't move the text or media
        let statusIcon = isPending ? '🕒' : (data.is_read == 1 ? '✓✓' : '✓');
        const statusHtml = isMe ? `<span class="msg-status" style="position:absolute; bottom:4px; right:8px; font-size:9px; opacity:0.7; color: ${data.is_read == 1 ? '#38bdf8' : 'inherit'}">${statusIcon}</span>` : '';
        
        // Assemble and append
        msg.innerHTML = `${mediaHtml}${textHtml}${statusHtml}`;
        chatBox.appendChild(msg);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
				        
    // --- 5. CORE LOGIC (Restored fid variable for stability) ---
	const handleSend = async () => {
        if (isRecording) stopRecording();
        
        // Safety: Wait for FileReader to finish before proceeding
        let waitCount = 0;
        while (isReadingFile && waitCount < 20) { 
            await new Promise(r => setTimeout(r, 100)); 
            waitCount++; 
        }
        
        const txt = chatIn.value.trim();
        const t = localStorage.getItem('pwoToken');
        if (!txt && !pendingFile) return;
        
        // Generate the unique ID used to track this message until the server confirms it
        const tempId = Date.now();
        
        // Render the "Pending" bubble (the duplication fix in 'render' will look for this tempId)
        render({ message: txt, is_me: true, temp_id: tempId }, false, true);
        
        const prog = document.getElementById('pwo-progress-container');
        const bar = document.getElementById('pwo-progress-bar');
        
        if (pendingFile) {
            if(prog) prog.classList.remove('hidden');
            
            const data = pendingFile.data;
            const CHUNK = 16384;
            const total = Math.ceil(data.length / CHUNK);
            const fid = tempId; // Use tempId as the file reference for stability
            
            for (let i = 0; i < total; i++) {
                ws.call('chat', 'uploadchunk', { 
                    file_id: fid, 
                    chunk: data.substring(i * CHUNK, (i + 1) * CHUNK), 
                    index: i, 
                    token: t 
                }, getAuthHeaders());
                
                if(bar) bar.style.width = ((i + 1) / total) * 100 + '%';
                // Small delay to prevent WebSocket buffer overflow in Chrome
                await new Promise(r => setTimeout(r, 15));
            }
            
            // Finalize the message with the file attachment
            ws.call('chat', 'send', { 
                message: txt, 
                file_id: fid, 
                file_name: pendingFile.name, 
                temp_id: tempId, 
                token: t 
            }, getAuthHeaders());
        } else {
            // Standard text-only message
            ws.call('chat', 'send', { 
                message: txt, 
                temp_id: tempId, 
                token: t 
            }, getAuthHeaders());
        }
        
        // UI Cleanup
        chatIn.value = ''; 
        pendingFile = null; 
        isReadingFile = false;
        document.getElementById('pwo-preview').classList.add('hidden');
        
        // Reset progress bar after a short delay
        setTimeout(() => { 
            if(prog) prog.classList.add('hidden'); 
            if(bar) bar.style.width = '0%'; 
        }, 500);
    };
		
    const stopRecording = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
            cancelAnimationFrame(animationId);
            if (audioCtx) audioCtx.close();
            clearInterval(timerInterval);
            if (recognition) recognition.stop();
            isRecording = false;
            micBtn.classList.remove('rec-active');
            document.getElementById('pwo-rec-panel').classList.add('hidden');
        }
    };

    // --- 6. ATTACHMENT & MIC ---
    const handleFile = (f) => {
        if (f.size > 5 * 1024 * 1024) { alert("File too large (max 5MB)"); return; }
        isReadingFile = true;
        document.getElementById('pwo-preview').classList.remove('hidden');
        document.getElementById('pwo-filename').innerText = "Reading...";
        const r = new FileReader();
        r.onload = (ev) => { 
            pendingFile = { data: ev.target.result, name: f.name }; 
            document.getElementById('pwo-filename').innerText = "✓ " + f.name; 
            isReadingFile = false; 
        };
        r.readAsDataURL(f);
    };

    document.getElementById('pwo-file-input').addEventListener('change', function() { if(this.files[0]) handleFile(this.files[0]); });
    document.getElementById('pwo-attach').onclick = () => document.getElementById('pwo-file-input').click();

    micBtn.onclick = async () => {
        if (!isRecording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream); audioChunks = [];
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                analyzer = audioCtx.createAnalyser();
                source = audioCtx.createMediaStreamSource(stream);
                source.connect(analyzer); analyzer.fftSize = 64;
                const canvas = document.getElementById('pwo-waveform');
                const ctx = canvas.getContext('2d');
                const draw = () => {
                    animationId = requestAnimationFrame(draw);
                    const bufLen = analyzer.frequencyBinCount, datArr = new Uint8Array(bufLen);
                    analyzer.getByteFrequencyData(datArr);
                    ctx.clearRect(0, 0, canvas.width, canvas.height); ctx.fillStyle = '#10b981';
                    let x = 0; for (let i = 0; i < bufLen; i++) {
                        const h = (datArr[i]/255)*canvas.height;
                        ctx.fillRect(x, canvas.height-h, 2, h); x += 4;
                    }
                };
                draw();
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = () => {
                    isReadingFile = true;
                    const blob = new Blob(audioChunks, { type: 'audio/webm' });
                    const r = new FileReader();
                    r.onload = (ev) => {
                        pendingFile = { data: ev.target.result, name: `voice_${Date.now()}.webm` };
                        document.getElementById('pwo-filename').innerText = "✓ Voice Ready";
                        document.getElementById('pwo-preview').classList.remove('hidden');
                        isReadingFile = false; stream.getTracks().forEach(t => t.stop());
                    };
                    r.readAsDataURL(blob);
                };
                seconds = 0;
                timerInterval = setInterval(() => {
                    seconds++;
                    document.getElementById('pwo-timer').innerText = `● ${Math.floor(seconds/60)}:${(seconds%60).toString().padStart(2,'0')}`;
                    if (seconds >= 120) stopRecording(); 
                }, 1000);
                mediaRecorder.start(); isRecording = true; micBtn.classList.add('rec-active');
                document.getElementById('pwo-rec-panel').classList.remove('hidden');
                
                if ('webkitSpeechRecognition' in window) {
                    recognition = new webkitSpeechRecognition();
                    recognition.continuous = true; recognition.interimResults = true;
                    recognition.onresult = (e) => {
                        let tr = ""; for (let i = e.resultIndex; i < e.results.length; ++i) if (e.results[i].isFinal) tr += e.results[i][0].transcript;
                        if(tr) chatIn.value = tr.trim();
                    };
                    recognition.start();
                }
            } catch (e) { alert("Mic access denied."); }
        } else stopRecording();
    };

    // --- 7. BINDINGS & LISTENERS ---
	document.getElementById('pwo-bubble').onclick = () => {
        const win = document.getElementById('pwo-window');
        const isOpen = win.style.display === 'none';
        win.style.display = isOpen ? 'flex' : 'none';
        
        if (isOpen) {
            const token = localStorage.getItem('pwoToken');
            if (token && !isSocketStarted) { ws.connect(); isSocketStarted = true; }
            // Tell server we read everything when opening
            if (token) ws.call('chat', 'mark_read', { target_user_id: 0, token: token }, getAuthHeaders());
        }
        stopTabFlash();
    };
		
    document.getElementById('pwo-export').onclick = () => {
        let content = "--- PWO CHAT LOG ---\n\n";
        document.querySelectorAll('#chat-box > div').forEach(div => {
            const isMe = div.classList.contains('msg-me');
            const txt = div.innerText.replace(/✓|🕒|✓✓/g, '').trim();
            content += `[${isMe ? 'USER' : 'SUPPORT'}] ${txt}\n`;
        });
        const blob = new Blob([content], { type: 'text/plain' });
        const a = document.createElement('a'); a.href = URL.createObjectURL(blob); 
        a.download = `Chat_Log_${new Date().toISOString().split('T')[0]}.txt`; a.click();
    };

    window.addEventListener('ws_connected', () => {
        document.getElementById('pwo-dot').style.backgroundColor = '#22c55e';
        document.getElementById('pwo-status').innerText = 'Connected';
        ws.call('chat', 'history', { token: localStorage.getItem('pwoToken') }, getAuthHeaders());
    });

	window.addEventListener('ws_new_message', e => {
        const data = e.detail.data || e.detail;
        render(data, true);
        
        // NEW: If window is open when message arrives, mark it read immediately
        const win = document.getElementById('pwo-window');
        const token = localStorage.getItem('pwoToken');
        if (win.style.display === 'flex' && token && !data.is_me) {
            ws.call('chat', 'mark_read', { target_user_id: 0, token: token }, getAuthHeaders());
        }
    });
		    
    window.addEventListener('ws_chat_history', e => { 
        chatBox.innerHTML = ''; 
        const h = e.detail.data || [];
        if (h.length === 0) render({ message: "Hello! How can we help?", sender_id: 0 }, false);
        else h.forEach(m => render(m, false)); 
    });

    window.addEventListener('ws_typing', () => { document.getElementById('pwo-typing').classList.remove('hidden'); setTimeout(()=>document.getElementById('pwo-typing').classList.add('hidden'), 3000); });
    
	window.addEventListener('ws_message_read', () => { 
        // Updates all your sent messages to blue double checks
        document.querySelectorAll('.msg-me .msg-status').forEach(el => { 
            el.innerText = '✓✓'; 
            el.style.color = '#38bdf8'; 
            el.style.opacity = '1';
        });
    });
		
    document.getElementById('pwo-do-login').onclick = async () => {
        const u = document.getElementById('pwo-user').value, p = document.getElementById('pwo-pass').value;
        const resp = await fetch('api/auth/login', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:u, password:p}) });
        const res = await resp.json();
        const token = res.data?.accessToken || res.token;
        if (token) { localStorage.setItem('pwoToken', token); localStorage.setItem('pwoUserId', res.data?.user_id || res.user_id); location.reload(); }
    };

    document.getElementById('pwo-logout').onclick = () => { localStorage.clear(); location.reload(); };
    document.getElementById('pwo-close').onclick = () => document.getElementById('pwo-window').style.display = 'none';
    document.getElementById('pwo-clear').onclick = () => { pendingFile = null; isReadingFile = false; document.getElementById('pwo-preview').classList.add('hidden'); };
    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = (e) => { 
        if(e.key === 'Enter') handleSend();
        else ws.call('chat', 'typing', { token: localStorage.getItem('pwoToken') }, getAuthHeaders());
    };
})();