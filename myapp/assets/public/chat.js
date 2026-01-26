/**
 # PWO Support - Master Script (File + Voice + Waveform + 2min Limit)
 */
(async function() {
    const SOCKET_URL = window.location.protocol === 'https:' 
        ? `wss://${window.location.hostname}/ws` 
        : `ws://localhost:8080`;
	
	document.head.insertAdjacentHTML('beforeend', `
	    <style>
	        /* Force Chrome's audio player to show standard controls */
	        audio::-webkit-media-controls-enclosure {
	            background-color: transparent !important;
	        }
	        audio::-webkit-media-controls-panel {
	            background-color: #f1f5f9; /* Slate-100 matches chat theme */
	        }
	        /* Fix for Firefox height consistency */
	        audio {
	            min-width: 200px;
	        }
	    </style>
	`);
		
    const loadScript = (src) => new Promise(r => {
        if (document.querySelector(`script[src="${src}"]`)) return r();
        const s = document.createElement('script'); s.src = src; s.onload = r; document.head.appendChild(s);
    });

    await Promise.all([loadScript('assets/public/tailwind.js'), loadScript('assets/public/wsclient.js')]);

    // --- UI STRUCTURE ---
    document.body.insertAdjacentHTML('beforeend', `
        <div id="pwo-window" style="display:none;" class="fixed bottom-24 right-6 w-80 md:w-96 h-[500px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden font-sans">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div class="flex items-center gap-2">
                    <div id="pwo-dot" class="w-2 h-2 bg-slate-300 rounded-full"></div>
                    <div><h3 class="font-bold text-sm">Live Support</h3><p id="pwo-status" class="text-[10px] opacity-75">Ready</p></div>
                </div>
                <button id="pwo-close" class="text-xl hover:opacity-50">&times;</button>
            </div>

            <div id="pwo-login-panel" class="hidden absolute inset-0 bg-white/95 z-50 flex flex-col items-center justify-center p-6 text-center">
                <div class="text-4xl mb-4">🔐</div>
                <h3 class="font-bold text-gray-800">Session Required</h3>
                <button onclick="window.location.reload()" class="bg-emerald-600 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg">Login</button>
            </div>

            <div id="pwo-rec-panel" class="hidden bg-slate-100 p-2 border-b flex items-center gap-3">
                <canvas id="pwo-waveform" class="w-24 h-8 bg-white rounded border"></canvas>
                <div class="flex-1">
                    <div class="text-[10px] font-bold text-red-600 animate-pulse">RECORDING...</div>
                    <div id="pwo-timer" class="text-xs font-mono text-gray-700">00:00 / 02:00</div>
                </div>
            </div>

            <div id="pwo-progress-container" class="h-1 bg-emerald-900 hidden shrink-0">
                <div id="pwo-progress-bar" class="h-full bg-yellow-400 w-0 transition-all duration-300"></div>
            </div>
            
            <div id="chat-box" class="p-4 bg-slate-50 overflow-y-auto flex-1 flex flex-col gap-3">
                <div id="chat-loading" class="text-center text-gray-400 text-[10px] mt-20 italic">Click bubble to connect...</div>
            </div>

            <div id="pwo-preview" class="hidden px-4 py-2 bg-emerald-50 border-t flex justify-between items-center shrink-0">
                <span id="pwo-filename" class="text-[10px] font-bold text-emerald-800 truncate mr-4"></span>
                <button id="pwo-clear" class="text-red-500 text-[10px] font-black hover:underline">REMOVE</button>
            </div>

            <div class="p-4 bg-white border-t flex gap-2 items-center shrink-0">
                <input type="file" id="pwo-file-input" class="hidden">
                <button id="pwo-attach" title="Attach File" class="text-xl text-gray-400 hover:text-emerald-600">📎</button>
                <button id="pwo-mic" title="Record Voice" class="text-xl text-gray-400 hover:text-red-500">🎤</button>
                <input id="chat-in" type="text" placeholder="Type message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full w-10 h-10 flex items-center justify-center shadow-md hover:bg-emerald-700">➤</button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] hover:scale-110 transition-transform">💬</button>
    `);

    // --- LOGIC & WS ---
    const ws = new WSClient(SOCKET_URL, 'pwoToken'); 
    let isSocketStarted = false, mediaRecorder = null, audioChunks = [], isRecording = false;
    let recTimer = null, recSeconds = 0, audioCtx, analyser, dataArray, animationId;
    
    const chatBox = document.getElementById('chat-box'), chatIn = document.getElementById('chat-in');
    const progBar = document.getElementById('pwo-progress-bar'), progCont = document.getElementById('pwo-progress-container');
    const fileInput = document.getElementById('pwo-file-input'), micBtn = document.getElementById('pwo-mic'), loginPanel = document.getElementById('pwo-login-panel');
    const recPanel = document.getElementById('pwo-rec-panel'), recTimerEl = document.getElementById('pwo-timer'), canvas = document.getElementById('pwo-waveform');
    const ctx = canvas.getContext('2d');

    let pendingFile = null;
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    function render(data, type) {
        if (!data) return;
        const isMe = type === 'me';
        const msg = document.createElement('div');
        msg.className = `p-3 rounded-2xl max-w-[85%] text-sm ${isMe ? 'bg-emerald-600 text-white self-end rounded-br-none' : 'bg-white border text-gray-800 self-start rounded-bl-none shadow-sm'}`;
        
        let media = '';
        if (data.file_path) {
            const path = data.file_path.toLowerCase();
            if (path.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                media = `<img src="${data.file_path}" class="rounded-lg mb-2 max-w-full h-auto cursor-pointer border" onclick="window.open('${data.file_path}')">`;
				} else if (path.match(/\.(webm|mp3|wav|ogg|m4a)$/i)) {
					// Explicitly styled for Chrome/Firefox consistency
					    media = `
					        <div class="voice-message-container my-2 p-2 bg-emerald-50/50 rounded-xl border border-emerald-100">
					            <audio controls class="w-full h-[45px] block">
					                <source src="${data.file_path}" type="audio/webm">
					                <source src="${data.file_path}" type="audio/ogg">
					                Your browser does not support audio.
					            </audio>
					            <div class="flex justify-between items-center px-1 mt-1">
					                <span class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider">Voice Note</span>
					                <a href="${data.file_path}" download class="text-[9px] text-emerald-500 hover:text-emerald-700 underline">Save File</a>
					            </div>
					        </div>`;
				} else {
                media = `<a href="${data.file_path}" target="_blank" class="block p-2 bg-black/5 rounded text-[10px] mb-2 font-bold italic truncate border">📄 ${data.file_name}</a>`;
            }
        }
        msg.innerHTML = `${media}<div>${data.message || ''}</div><div class="text-[8px] opacity-50 mt-1 text-right">${data.time || ''}</div>`;
        chatBox.appendChild(msg);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    const startChatService = () => {
        if (isSocketStarted) return;
        ws.connect();
        isSocketStarted = true;
        window.addEventListener('ws_connected', () => {
            document.getElementById('pwo-status').innerText = 'Online';
            document.getElementById('pwo-dot').className = 'w-2 h-2 bg-emerald-400 rounded-full animate-pulse';
            ws.call('chat', 'history', { token: localStorage.getItem('pwoToken') }, getAuthHeaders());
        });
        window.addEventListener('ws_error', e => {
            if (e.detail?.code === 401) loginPanel.classList.remove('hidden');
            document.getElementById('pwo-status').innerText = 'Offline';
            document.getElementById('pwo-dot').className = 'w-2 h-2 bg-red-500 rounded-full';
        });
        window.addEventListener('ws_chat_history', e => {
            if (document.getElementById('chat-loading')) document.getElementById('chat-loading').remove();
            chatBox.innerHTML = '';
            (e.detail.data || []).forEach(item => render(item, item.is_me ? 'me' : 'remote'));
        });
        window.addEventListener('ws_chat_confirmation', e => render(e.detail.data || e.detail, 'me'));
        window.addEventListener('ws_new_message', e => render(e.detail.data || e.detail, 'remote'));
    };

	const handleSend = async () => {
	    // 1. If we are recording, stop it and WAIT for the data
	    if (isRecording) {
	        mediaRecorder.stop();
	        isRecording = false;
	        // Give the FileReader a moment to finish
	        await new Promise(r => setTimeout(r, 300)); 
	    }

	    const txt = chatIn.value.trim();
	    const t = localStorage.getItem('pwoToken');

	    // 2. SAFETY CHECK: Prevent the "data of null" error
	    if (!txt && !pendingFile) {
	        console.warn("Nothing to send: Text and File are both empty.");
	        return;
	    }

	    try {
	        if (pendingFile && pendingFile.data) { // Double check .data exists
	            progCont.classList.remove('hidden');
	            const CHUNK_SIZE = 32768;
	            const totalChunks = Math.ceil(pendingFile.data.length / CHUNK_SIZE);
	            const fid = Date.now();

	            for (let i = 0; i < totalChunks; i++) {
	                const chunk = pendingFile.data.substring(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
	                ws.call('chat', 'uploadchunk', { file_id: fid, chunk: chunk, index: i, token: t }, getAuthHeaders());
	                progBar.style.width = `${Math.round(((i + 1) / totalChunks) * 100)}%`;
	                await new Promise(r => setTimeout(r, 20));
	            }
	            await new Promise(r => setTimeout(r, 100));
	            ws.call('chat', 'send', { message: txt, file_id: fid, file_name: pendingFile.name, token: t }, getAuthHeaders());
	        } else if (txt) {
	            ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
	        }

	        // 3. Cleanup
	        chatIn.value = ''; 
	        pendingFile = null;
	        recPanel.classList.add('hidden');
	        micBtn.innerHTML = '🎤';
	        micBtn.classList.remove('text-red-600', 'animate-pulse');
	        document.getElementById('pwo-preview').classList.add('hidden');
	        setTimeout(() => { progCont.classList.add('hidden'); progBar.style.width = '0%'; }, 1000);
	    } catch (err) {
	        console.error("Send Error:", err);
	    }
	};
			
    // --- WAVEFORM DRAWING ---
    function drawWave() {
        if (!isRecording) return;
        animationId = requestAnimationFrame(drawWave);
        analyser.getByteTimeDomainData(dataArray);
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.lineWidth = 2; ctx.strokeStyle = '#10b981'; ctx.beginPath();
        let sliceWidth = canvas.width * 1.0 / dataArray.length, x = 0;
        for (let i = 0; i < dataArray.length; i++) {
            let v = dataArray[i] / 128.0, y = v * canvas.height / 2;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
            x += sliceWidth;
        }
        ctx.lineTo(canvas.width, canvas.height / 2); ctx.stroke();
    }

    // --- VOICE LOGIC (2 MIN LIMIT + WAVEFORM) ---
	micBtn.onclick = async () => {
	    if (!isRecording) {
	        try {
	            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
	            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
	            analyser = audioCtx.createAnalyser();
	            const source = audioCtx.createMediaStreamSource(stream);
	            source.connect(analyser);
	            analyser.fftSize = 256;
	            dataArray = new Uint8Array(analyser.frequencyBinCount);

	            const mimeType = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/ogg';
	            mediaRecorder = new MediaRecorder(stream, { mimeType });
	            audioChunks = [];

	            mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };

	            // We wrap the stop logic in a promise so handleSend can await it
	            mediaRecorder.onstop = () => {
	                return new Promise((resolve) => {
	                    const audioBlob = new Blob(audioChunks, { type: mimeType });
	                    const reader = new FileReader();
	                    reader.onload = (e) => {
	                        pendingFile = { 
	                            data: e.target.result, 
	                            name: `voice_${Date.now()}.webm` 
	                        };
	                        resolve(); // Signal that pendingFile is now ready
	                    };
	                    reader.readAsDataURL(audioBlob);
	                    stream.getTracks().forEach(t => t.stop());
	                    if (audioCtx) audioCtx.close();
	                    cancelAnimationFrame(animationId);
	                });
	            };

	            mediaRecorder.start();
	            isRecording = true;
	            recSeconds = 0;
	            recPanel.classList.remove('hidden');
	            micBtn.innerHTML = '🛑'; micBtn.classList.add('text-red-600', 'animate-pulse');
	            drawWave();
	            
	            recTimer = setInterval(() => {
	                recSeconds++;
	                recTimerEl.innerText = `${Math.floor(recSeconds / 60).toString().padStart(2, '0')}:${(recSeconds % 60).toString().padStart(2, '0')} / 02:00`;
	                if (recSeconds >= 120 && isRecording) micBtn.click();
	            }, 1000);

	        } catch (err) { console.error("Mic Error:", err); }
	    } else {
	        // Trigger the stop process
	        mediaRecorder.stop(); 
	        isRecording = false;
	        clearInterval(recTimer);
	        recPanel.classList.add('hidden');
	        micBtn.innerHTML = '🎤'; micBtn.classList.remove('text-red-600', 'animate-pulse');
	        
	        // Wait for the onstop promise to finish before handleSend (if called from here)
	        await new Promise(r => setTimeout(r, 250)); 
	        handleSend();
	    }
	};
		
    // --- UI EVENTS ---
    document.getElementById('pwo-bubble').onclick = () => {
        const win = document.getElementById('pwo-window');
        if (win.style.display === 'none') { win.style.display = 'flex'; startChatService(); } 
        else { win.style.display = 'none'; }
    };
    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = e => { if (e.key === 'Enter') handleSend(); };
    document.getElementById('pwo-attach').onclick = () => fileInput.click();
    document.getElementById('pwo-close').onclick = () => document.getElementById('pwo-window').style.display = 'none';
    document.getElementById('pwo-clear').onclick = () => { pendingFile = null; document.getElementById('pwo-preview').classList.add('hidden'); };
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