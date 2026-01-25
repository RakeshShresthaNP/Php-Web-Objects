/**
 * PWO Autonomous Support System - Visitor Edition
 * Copyright Rakesh Shrestha
 */
(async function() {
    // 1. CONFIGURATION
    const WS_CLIENT_PATH = 'assets/dashboard/wsclient.js';
    const SOCKET_URL = window.location.protocol === 'https:' 
        ? `wss://${window.location.hostname}/ws` 
        : `ws://127.0.0.1:8080`;

    // 2. DEPENDENCY LOADER
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
        await Promise.all([
            loadScript('https://cdn.tailwindcss.com'),
            loadScript(WS_CLIENT_PATH)
        ]);
        console.log("✅ Chat Dependencies Loaded");
    } catch (err) {
        console.error("❌ Chat Loader Error:", err);
        return;
    }

    // 3. INJECT UI COMPONENTS
    const bubbleHtml = `
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] hover:scale-110 active:scale-95 transition-all pointer-events-auto">
            💬
            <span id="pwo-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-full border-2 border-white">0</span>
        </button>`;

    const windowHtml = `
        <div id="pwo-window" class="hidden fixed bottom-24 right-6 w-80 md:w-96 h-[500px] bg-white rounded-2xl shadow-2xl z-[9999] border border-gray-200 flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-300 pointer-events-auto">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-sm">Live Support</h3>
                    <p class="text-[10px] opacity-80" id="pwo-status">Connecting...</p>
                </div>
                <button id="pwo-close" class="text-2xl hover:rotate-90 transition-transform">&times;</button>
            </div>
            <div id="chat-box" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3 scroll-smooth">
            </div>
            <div class="p-4 bg-white border-t flex gap-2">
                <input id="chat-in" type="text" placeholder="Type a message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500 text-gray-800">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full hover:bg-emerald-700 transition-colors">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>`;

    document.body.insertAdjacentHTML('beforeend', bubbleHtml);
    document.body.insertAdjacentHTML('beforeend', windowHtml);

    // 4. WEBSOCKET INITIALIZATION
    // Visitors might not have a token, so we pass an empty string
    const ws = new WSClient(SOCKET_URL, window.pwoToken || "");
    ws.connect();

    // 5. SELECTORS
    const bubble = document.getElementById('pwo-bubble');
    const win = document.getElementById('pwo-window');
    const closeBtn = document.getElementById('pwo-close');
    const chatIn = document.getElementById('chat-in');
    const chatBox = document.getElementById('chat-box');
    const badge = document.getElementById('pwo-badge');
    let unreadCount = 0;

    // 6. UI LOGIC (Optimized for Visitors)
    bubble.onclick = () => {
        const isHidden = win.classList.contains('hidden');
        win.classList.toggle('hidden');
        win.classList.toggle('flex');
        
        if (isHidden) {
            unreadCount = 0;
            badge.classList.add('hidden');
            
            // Show welcome message ONLY if the box is empty
            if (chatBox.innerHTML.trim() === '') {
                renderMessage({
                    sender: 'Support Bot',
                    message: '👋 Hello! Welcome to our site. How can we help you today?',
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                }, 'bot');
            }
            
            setTimeout(() => chatIn.focus(), 100);
        }
    };

    closeBtn.onclick = () => {
        win.classList.add('hidden');
        win.classList.remove('flex');
    };

    // 7. WEBSOCKET EVENT LISTENERS
    window.addEventListener('ws_connected', () => {
        document.getElementById('pwo-status').innerText = 'Online';
    });

    window.addEventListener('ws_new_message', (e) => {
        if (win.classList.contains('hidden')) {
            unreadCount++;
            badge.innerText = unreadCount;
            badge.classList.remove('hidden');
        }
        renderMessage(e.detail, 'remote');
    });

    window.addEventListener('ws_chat_confirmation', (e) => {
        renderMessage(e.detail.data, 'me');
    });

    // 8. SEND LOGIC
    const handleSend = () => {
        const text = chatIn.value.trim();
        if (text) {
            // ONLY call 'send' when the user actually clicks the button or hits enter
            ws.call('chat', 'send', { message: text });
            chatIn.value = '';
        }
    };

    document.getElementById('chat-send').onclick = handleSend;
    chatIn.onkeypress = (e) => { if (e.key === 'Enter') handleSend(); };

    // 9. RENDERER
    function renderMessage(data, type) {
        if (!data) return;
        const isMe = type === 'me';
        const isBot = type === 'bot';
        
        const msgDiv = document.createElement('div');
        
        let bgColor = 'bg-white text-gray-800 border border-gray-100';
        let align = 'self-start rounded-tl-none';
        let labelColor = 'text-emerald-700';

        if (isMe) {
            bgColor = 'bg-emerald-600 text-white';
            align = 'self-end rounded-tr-none';
            labelColor = 'text-emerald-200';
        } else if (isBot) {
            bgColor = 'bg-blue-600 text-white';
            align = 'self-start rounded-tl-none';
            labelColor = 'text-blue-200';
        }

        msgDiv.className = `max-w-[85%] p-3 rounded-2xl text-sm shadow-sm animate-in fade-in zoom-in-95 duration-200 ${bgColor} ${align}`;
        
        msgDiv.innerHTML = `
            <p class="text-[10px] font-bold mb-1 ${labelColor}">${data.sender || 'System'}</p>
            <p class="leading-relaxed">${data.message}</p>
            <p class="text-[9px] mt-1 opacity-50 text-right font-mono">${data.time || ''}</p>
        `;
        chatBox.appendChild(msgDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
})();