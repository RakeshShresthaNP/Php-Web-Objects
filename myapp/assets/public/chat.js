import WSClient from './wsclient.js';
import { PWO_STYLES, PWO_HTML } from './pwo-templates.js';
import { render } from './pwo-ui.js';
import { 
    handleSend, handleFile, handleMic, 
    initDeleteHandler, initSearchHandler, processOfflineQueue,
    initAutoExpand, initDragAndDrop, initEmojiPicker, initExportHandler // Add these 4
} from './pwo-logic.js';
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

const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 18) return "Good afternoon";
    return "Good evening";
};

const WELCOME_DATA = { 
    message: `${getGreeting()}! How can we help you today?`, 
    is_me: false, 
    system: true 
};

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

const SOCKET_URL = window.location.protocol === 'https:' ? 
    `wss://${window.location.hostname}/pwo/wss` : `ws://${window.location.hostname}:8080`;

const ws = new WSClient(SOCKET_URL, Auth.getToken() || "");
const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

function startLogic() {
    const emojiBtn = document.getElementById('pwo-emoji-btn');
    
    if (emojiBtn) {
        console.log("UI Ready. Wiring logic...");
        
        initEmojiPicker();
        initExportHandler();
        initAutoExpand();
        initDragAndDrop(state); // Now 'state' is safe to use
        
        initDeleteHandler(ws);  // Now 'ws' is safe to use
        initSearchHandler();
    } else {
        setTimeout(startLogic, 100);
    }
}

// UI References
const win = document.getElementById('pwo-window');
const bubble = document.getElementById('pwo-bubble');
const textarea = document.getElementById('chat-in');
const sendBtn = document.getElementById('chat-send');

// --- 4. AUTHENTICATION ---
if (!Auth.isAuthenticated()) {
    document.getElementById('pwo-auth-overlay').classList.remove('hidden');
}

document.getElementById('pwo-do-login').addEventListener('click', async () => {
    const u = document.getElementById('pwo-user').value;
    const p = document.getElementById('pwo-pass').value;
    const result = await Auth.login(u, p);

    if (result.success) {
        const id = result.id || result.user_id || result.data?.id;
        if (id) localStorage.setItem('pwoUserId', id.toString());
        location.reload(); 
    } else {
        alert(result.error || "Login Failed");
    }
});

document.getElementById('pwo-logout').addEventListener('click', () => {
    Auth.logout();
    location.reload();
});

// --- 5. UI EVENT BINDINGS ---

// Toggle Window
bubble.addEventListener('click', () => {
    // Check if currently hidden
    const isHidden = win.style.display === 'none' || win.style.display === '';
    win.style.display = isHidden ? 'flex' : 'none';

    if (isHidden) {
        // This starts all your Task 2 & 3 logic once correctly
        startLogic();
        
        if (Auth.isAuthenticated()) {
            if (!state.isSocketStarted) { 
                ws.connect(); 
                state.isSocketStarted = true; 
            }
            ws.call('chat', 'markread', { target_user_id: 0, token: Auth.getToken() }, getAuthHeaders());
        }
    }
});

// Message Input Logic (Merged)
textarea.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendBtn.click();
    } else if (Auth.isAuthenticated()) {
        ws.call('chat', 'typing', { token: Auth.getToken() }, getAuthHeaders());
    }
});

// Send Actions
sendBtn.addEventListener('click', () => handleSend(state, ws));

document.getElementById('pwo-mic').addEventListener('click', () => handleMic(state));

document.getElementById('pwo-close').addEventListener('click', () => win.style.display = 'none');

// File Handling
document.getElementById('pwo-attach').addEventListener('click', () => document.getElementById('pwo-file-input').click());

document.getElementById('pwo-file-input').addEventListener('change', (e) => handleFile(e.target.files[0], state));

document.getElementById('pwo-clear').addEventListener('click', () => { 
    state.pendingFile = null; 
    document.getElementById('pwo-preview').classList.add('hidden'); 
});

// --- 6. WEBSOCKET EVENT LISTENERS ---
window.addEventListener('ws_connected', () => {
    document.getElementById('pwo-dot').style.backgroundColor = '#22c55e';
    document.getElementById('pwo-status').innerText = 'Connected';
    processOfflineQueue(ws);
    ws.call('chat', 'history', { token: Auth.getToken() }, getAuthHeaders());
});

window.addEventListener('ws_new_message', e => {
    const data = e.detail.data || e.detail;
	document.getElementById('pwo-typing')?.classList.add('hidden');
    render(data, true);
    if (win.style.display === 'flex' && !data.is_me) {
        ws.call('chat', 'markread', { target_user_id: 0, token: Auth.getToken() }, getAuthHeaders());
    }
    textarea.style.height = '36px'; // Reset height after message sent/received
});

window.addEventListener('ws_chat_history', e => {
    const history = e.detail.data || e.detail || [];
    const chatBox = document.getElementById('chat-box');
    chatBox.innerHTML = ''; 
    render(WELCOME_DATA, false);
    history.forEach(m => render(m, false));
    chatBox.scrollTop = chatBox.scrollHeight;
});

window.addEventListener('ws_message_read', () => {
    document.querySelectorAll('.msg-status').forEach(el => {
        el.innerText = '✓✓'; 
        el.parentElement.classList.replace('text-gray-400', 'text-sky-400');
    });
});

window.addEventListener('ws_message_deleted', (e) => {
    const deletedId = e.detail.id || e.detail.data?.id;
    if (!deletedId) return;
    const btn = document.querySelector(`.pwo-delete-btn[data-id="${deletedId}"]`);
    if (btn) btn.closest('.mb-4')?.remove();
});

window.addEventListener('ws_typing', (e) => {
    const typingIndicator = document.getElementById('pwo-typing');
    if (!typingIndicator) return;

    // Show the indicator
    typingIndicator.classList.remove('hidden');

    // Auto-hide after 3 seconds of no typing activity
    clearTimeout(window.typingTimer);
    window.typingTimer = setTimeout(() => {
        typingIndicator.classList.add('hidden');
    }, 3000);
});
