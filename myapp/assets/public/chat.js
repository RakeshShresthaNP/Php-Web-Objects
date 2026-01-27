import WSClient from './wsclient.js';
import { PWO_STYLES, PWO_HTML } from './pwo-templates.js';
import { render } from './pwo-ui.js';
import { handleSend, handleFile, handleMic } from './pwo-logic.js';
import { initDeleteHandler, initSearchHandler, processOfflineQueue } from './pwo-logic.js';
import { Auth } from './pwo-auth.js';

// --- 1. ASYNC DEPENDENCY LOADING ---
async function loadTailwind() {
    if (window.tailwind) return;
    return new Promise((resolve) => {
        const script = document.createElement('script');
        script.src = 'assets/public/tailwind.js';
        script.onload = resolve;
        document.head.appendChild(script);
    });
}

await loadTailwind();

// --- 2. UI INITIALIZATION ---
document.head.insertAdjacentHTML('beforeend', PWO_STYLES);
document.body.insertAdjacentHTML('beforeend', PWO_HTML);

const WELCOME_DATA = { 
    message: "Hello! How can we help you today?", 
    is_me: false,
    system: true 
};

// Render initial welcome immediately
render(WELCOME_DATA, false);

// --- 3. STATE & SOCKET SETUP ---
const state = {
    isReadingFile: false,
    isSocketStarted: false,
    isRecording: false,
    pendingFile: null,
    mediaRecorder: null,
    audioChunks: []
};

// Ensure WSClient is initialized using the Imported Class
const SOCKET_URL = window.location.protocol === 'https:' ? 
    `wss://${window.location.hostname}/pwo/wss` : `ws://${window.location.hostname}:8080`;

const ws = new WSClient(SOCKET_URL, Auth.getToken() || "");
const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

const win = document.getElementById('pwo-window');
const bubble = document.getElementById('pwo-bubble');
const chatIn = document.getElementById('chat-in');

initDeleteHandler(ws);
initSearchHandler();

// --- ADD: Auto-Expanding Textarea ---
chatIn.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (Math.min(this.scrollHeight, 128)) + 'px'; 
});

// --- 4. AUTHENTICATION LOGIC ---
if (!Auth.isAuthenticated()) {
    document.getElementById('pwo-auth-overlay').classList.remove('hidden');
}

document.getElementById('pwo-do-login').onclick = async () => {
    const u = document.getElementById('pwo-user').value;
    const p = document.getElementById('pwo-pass').value;
    const result = await Auth.login(u, p);
    if (result.success) {
        location.reload(); 
    } else {
        alert(result.error || "Login Failed");
    }
};

document.getElementById('pwo-logout').onclick = () => {
    Auth.logout();
    location.reload();
};

// --- 5. UI BINDINGS ---
bubble.onclick = () => {
    const opening = win.style.display === 'none' || win.style.display === '';
    win.style.display = opening ? 'flex' : 'none';
    
    if (opening && Auth.isAuthenticated()) {
        if (!state.isSocketStarted) { 
            ws.connect(); 
            state.isSocketStarted = true; 
        }
        ws.call('chat', 'markread', { target_user_id: 0, token: Auth.getToken() }, getAuthHeaders());
    }
};

// Bind logic handlers
document.getElementById('chat-send').onclick = () => handleSend(state, ws);
document.getElementById('pwo-mic').onclick = () => handleMic(state);
document.getElementById('pwo-close').onclick = () => win.style.display = 'none';

// File Handling
document.getElementById('pwo-attach').onclick = () => document.getElementById('pwo-file-input').click();
document.getElementById('pwo-file-input').onchange = (e) => handleFile(e.target.files[0], state);
document.getElementById('pwo-clear').onclick = () => { 
    state.pendingFile = null; 
    document.getElementById('pwo-preview').classList.add('hidden'); 
};

// Export Logic
document.getElementById('pwo-export').onclick = () => {
    let content = "--- PWO CHAT LOG ---\n\n";
    document.querySelectorAll('#chat-box > div').forEach(div => {
        const isMe = div.classList.contains('justify-end'); // Check alignment for sender
        const txt = div.innerText.replace(/✓|🕒|✓✓/g, '').trim();
        content += `[${isMe ? 'USER' : 'SUPPORT'}] ${txt}\n`;
    });
    const blob = new Blob([content], { type: 'text/plain' });
    const a = document.createElement('a'); 
    a.href = URL.createObjectURL(blob); 
    a.download = `Chat_Log_${new Date().toISOString().split('T')[0]}.txt`; 
    a.click();
};

// Keyboard Handling
chatIn.onkeypress = (e) => { 
    if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSend(state, ws);
    } else if (Auth.isAuthenticated()) {
        ws.call('chat', 'typing', { token: Auth.getToken() }, getAuthHeaders());
    }
};

// --- 6. WEBSOCKET EVENT LISTENERS ---
window.addEventListener('ws_connected', () => {
    document.getElementById('pwo-dot').style.backgroundColor = '#22c55e';
    document.getElementById('pwo-status').innerText = 'Connected';
    
    // Auto-sync messages sent while the user was offline
    processOfflineQueue(ws);
    
    ws.call('chat', 'history', { token: Auth.getToken() }, getAuthHeaders());
});

window.addEventListener('ws_new_message', e => {
    const data = e.detail.data || e.detail;
    render(data, true);
    if (win.style.display === 'flex' && !data.is_me) {
        ws.call('chat', 'markread', { target_user_id: 0, token: Auth.getToken() }, getAuthHeaders());
    }
});

window.addEventListener('ws_chat_history', e => {
    const chatBox = document.getElementById('chat-box');
    chatBox.innerHTML = ''; // Clear for history load
    
    // Re-render Welcome Message first so it stays at the top of the history
    render(WELCOME_DATA, false);
    
    const history = e.detail.data || e.detail || [];
    history.forEach(m => render(m, false));
    
    chatBox.scrollTop = chatBox.scrollHeight;
});

window.addEventListener('ws_message_read', () => {
    document.querySelectorAll('.msg-status').forEach(el => {
        el.innerText = '✓✓'; 
        el.parentElement.classList.replace('text-gray-400', 'text-sky-400');
    });
});

window.addEventListener('ws_typing', () => {
    const t = document.getElementById('pwo-typing');
    t.classList.remove('hidden');
    clearTimeout(window.typingTimer);
    window.typingTimer = setTimeout(() => t.classList.add('hidden'), 3000);
});
