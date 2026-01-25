/**
 * PWO Autonomous Support System - Pro Integrated Edition
 * FINAL STABLE VERSION: Includes File Picker Fix, Auth, Chunked Upload, Clear Chat
 */
(async function() {
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const WS_CLIENT_PATH = 'assets/public/wsclient.js';
    const SOCKET_URL = window.location.protocol === 'https:' 
        ? `wss://${window.location.hostname}/ws` 
        : `ws://localhost:8080`;

    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) return resolve();
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    };

    try {
        await Promise.all([loadScript('https://cdn.tailwindcss.com'), loadScript(WS_CLIENT_PATH)]);
    } catch (err) { return; }

    // UI INJECTION
    const windowHtml = `
        <div id="pwo-window" class="hidden fixed bottom-24 right-6 w-80 md:w-96 h-[550px] bg-white rounded-2xl shadow-2xl z-[9999] border flex flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-4">
            <div class="bg-emerald-600 p-4 text-white shrink-0 relative">
                <div class="flex justify-between items-center">
                    <div><h3 class="font-bold text-sm">Live Support</h3><p class="text-[10px] opacity-80" id="pwo-status">Connecting...</p></div>
                    <div class="flex items-center gap-3">
                        <button id="pwo-clear" title="Clear Chat" class="opacity-70 hover:opacity-100 transition-opacity">
                            <svg viewBox="0 0 24 24" class="w-4 h-4 fill-none stroke-current stroke-2"><path d="M3 6h18m-2 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                        </button>
                        <button id="pwo-close" class="text-2xl hover:rotate-90 transition-transform">&times;</button>
                    </div>
                </div>
                <div id="pwo-progress-container" class="absolute bottom-0 left-0 w-full h-1 bg-emerald-900 hidden">
                    <div id="pwo-progress-bar" class="h-full bg-yellow-400 transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
            <div id="chat-box" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3 scroll-smooth relative"></div>
            <div class="p-4 bg-white border-t flex gap-2 items-center relative shrink-0">
                <input type="file" id="pwo-file-input" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <button id="pwo-attach" type="button" class="text-gray-400 hover:text-emerald-600 p-1">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-none stroke-current stroke-2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                </button>
                <input id="chat-in" type="text" placeholder="Type a message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full hover:bg-emerald-700 active:scale-90">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] hover:scale-110 active:scale-95 transition-all">💬</button>`;

    document.body.insertAdjacentHTML('beforeend', windowHtml);

    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    ws.connect();

    const win = document.getElementById('pwo-window'),
          chatBox = document.getElementById('chat-box'),
          chatIn = document.getElementById('chat-in'),
          sendBtn = document.getElementById('chat-send'),
          fileInput = document.getElementById('pwo-file-input'),
          attachBtn = document.getElementById('pwo-attach'),
          statusEl = document.getElementById('pwo-status'),
          progCont = document.getElementById('pwo-progress-container'),
          progBar = document.getElementById('pwo-progress-bar'),
          clearBtn = document.getElementById('pwo-clear');

    let pendingFile = null;

    const getActiveToken = () => localStorage.getItem('pwoToken');
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });
    const scrollToBottom = () => { chatBox.scrollTop = chatBox.scrollHeight; };

    // --- CRITICAL FIX FOR FILE PICKER ---
    attachBtn.addEventListener('click', (e) => {
        e.preventDefault();
        fileInput.click();
    });
    // ------------------------------------

    fileInput.onchange = () => {
        const file = fileInput.files[0];
        if (!file) return;
        if (file.size > MAX_FILE_SIZE) { alert("Max 5MB"); fileInput.value = ""; return; }
        const reader = new FileReader();
        reader.onload = (e) => {
            pendingFile = { data: e.target.result, name: file.name };
            chatIn.placeholder = `📎 Ready: ${file.name}`;
        };
        reader.readAsDataURL(file);
    };

    function renderMessage(rawData, type) {
        const data = rawData.data ? rawData.data : rawData;
        if (!data) return;
        const isMe = type === 'me';
        let text = "", fileUrl = null, fileName = "";
        try {
            const msgObj = (typeof data.message === 'string' && data.message.startsWith('{')) ? JSON.parse(data.message) : data.message;
            if (typeof msgObj === 'object' && msgObj !== null) {
                text = msgObj.text || ""; fileUrl = msgObj.file || null; fileName = msgObj.file_name || "Attachment";
            } else { text = data.message; }
        } catch (e) { text = data.message; }

        let fileHtml = '';
        if (fileUrl) {
            const isImg = fileUrl.match(/\.(jpg|jpeg|png|webp|gif)$/i);
            fileHtml = isImg 
                ? `<div class="mt-2 rounded-lg border overflow-hidden relative group bg-gray-100">
                    <a href="${fileUrl}" target="_blank"><img src="${fileUrl}" class="w-full h-auto block"></a>
                    <a href="${fileUrl}" download="${fileName}" class="absolute top-2 right-2 bg-black/50 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg viewBox="0 0 24 24" class="w-4 h-4 fill-none stroke-current stroke-2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4m7-5l5 5m0 0l5-5m-5 5V3"/></svg>
                    </a>
                   </div>`
                : `<div class="flex items-center justify-between gap-2 p-2 mt-2 bg-black/5 rounded-lg border text-xs">
                    <span class="truncate font-bold">📕 ${fileName}</span>
                    <a href="${fileUrl}" download="${fileName}" class="text-emerald-700 font-bold hover:underline">Download</a>
                   </div>`;
        }

        const msgDiv = document.createElement('div');
        msgDiv.className = `max-w-[85%] p-3 rounded-2xl text-sm shadow-sm ${isMe ? 'bg-emerald-600 text-white self-end rounded-tr-none' : 'bg-white text-gray-800 border self-start rounded-tl-none'} mb-1 animate-in fade-in zoom-in-95`;
        msgDiv.innerHTML = `<p class="text-[10px] font-bold mb-1 opacity-75">${data.sender || (isMe ? "You" : "Support")}</p>
                            <div class="leading-relaxed whitespace-pre-wrap">${text || ""}</div>${fileHtml}
                            <p class="text-[9px] mt-1 opacity-50 text-right">${data.time || ''}</p>`;
        chatBox.appendChild(msgDiv);
        scrollToBottom();
    }

    const handleSend = async () => {
        const text = chatIn.value.trim(), token = getActiveToken();
        if (!token) return showLoginOverlay();
        if (!text && !pendingFile) return;

        if (pendingFile) {
            progCont.classList.remove('hidden');
            const CHUNK_SIZE = 64 * 1024, total = Math.ceil(pendingFile.data.length / CHUNK_SIZE), fileId = Date.now();
            try {
				for (let i = 0; i < total; i++) {
				    const chunk = pendingFile.data.substring(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
				    const pct = Math.round(((i + 1) / total) * 100);
				    statusEl.innerText = `Uploading: ${pct}%`;
				    progBar.style.width = `${pct}%`;

				    // CHANGED TO uploadchunk (no underscore)
				    ws.call('chat', 'uploadchunk', { file_id: fileId, chunk, index: i, token }, getAuthHeaders());
				    
				    await new Promise(r => setTimeout(r, 10));
				}
				ws.call('chat', 'send', { message: text, file_id: fileId, file_name: pendingFile.name, token }, getAuthHeaders());
            } catch (err) { statusEl.innerText = "Upload Failed"; return; }
        } else {
            ws.call('chat', 'send', { message: text, token }, getAuthHeaders());
        }
        chatIn.value = ''; pendingFile = null; chatIn.placeholder = "Type a message...";
        setTimeout(() => { progCont.classList.add('hidden'); progBar.style.width = '0%'; statusEl.innerText = 'Online'; }, 1000);
    };

    sendBtn.onclick = handleSend;
    chatIn.onkeypress = (e) => { if (e.key === 'Enter') handleSend(); };
    clearBtn.onclick = () => { if (confirm("Clear history?")) { ws.call('chat', 'clear_history', { token: getActiveToken() }, getAuthHeaders()); chatBox.innerHTML = ''; } };

    document.getElementById('pwo-bubble').onclick = () => {
        win.classList.toggle('hidden');
        if (!win.classList.contains('hidden')) {
            const token = getActiveToken();
            if (token) ws.call('chat', 'history', { token }, getAuthHeaders());
            else showLoginOverlay();
            scrollToBottom();
        }
    };

    function showLoginOverlay() {
        if (document.getElementById('pwo-login-box')) return;
        const loginHtml = `<div id="pwo-login-box" class="absolute inset-0 bg-white/95 backdrop-blur-sm z-[10000] flex items-center justify-center p-4">
            <div class="bg-white p-6 rounded-2xl shadow-2xl border w-full max-w-[280px]">
                <p class="text-gray-800 font-bold text-sm mb-4 text-center">Login to sync history</p>
                <input id="pwo-user" type="text" placeholder="Username" class="w-full mb-2 p-2 border rounded-lg text-sm outline-none">
                <input id="pwo-pass" type="password" placeholder="Password" class="w-full mb-4 p-2 border rounded-lg text-sm outline-none">
                <button id="pwo-do-login" class="w-full bg-emerald-600 text-white py-2 rounded-lg font-bold text-sm">Login</button>
            </div>
        </div>`;
        win.insertAdjacentHTML('afterbegin', loginHtml);
        document.getElementById('pwo-do-login').onclick = async () => {
            const username = document.getElementById('pwo-user').value;
            const password = document.getElementById('pwo-pass').value;
            const resp = await fetch('api/auth/login', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ username, password }) });
            const result = await resp.json();
            const token = result.data?.accessToken || result.accessToken || result.token;
            if (token) {
                localStorage.setItem('pwoToken', token);
                document.getElementById('pwo-login-box').remove();
                ws.call('chat', 'history', { token }, getAuthHeaders());
            } else { alert("Login failed"); }
        };
    }

    window.addEventListener('ws_connected', () => { statusEl.innerText = 'Online'; });
    window.addEventListener('ws_chat_history', (e) => { chatBox.innerHTML = ''; (e.detail.data || []).forEach(msg => renderMessage(msg, 'remote')); scrollToBottom(); });
    window.addEventListener('ws_chat_confirmation', (e) => renderMessage(e.detail, 'me'));
    window.addEventListener('ws_new_message', (e) => renderMessage(e.detail, 'remote'));
    document.getElementById('pwo-close').onclick = () => win.classList.add('hidden');
})();