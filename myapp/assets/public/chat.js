import WSClient from './wsclient.js';
import { PWO_STYLES, PWO_HTML } from './pwo-templates.js';
import { render } from './pwo-ui.js';
import { 
    handleSend, handleFile, handleMic, 
    initDeleteHandler, initSearchHandler, processOfflineQueue,
    initAutoExpand, initDragAndDrop, initEmojiPicker, initExportHandler, initLightboxHandler
} from './pwo-logic.js';
import { Auth } from './pwo-auth.js';
import { ChatSearch } from './pwo-search.js';

// --- 1. ASYNC DEPENDENCY LOADING ---
async function loadDependencies() {
    const scripts = [
        { id: 'tw-script', src: 'assets/public/tailwind.js' },
        { id: 'dexie-script', src: 'assets/public/dexie.js' }
    ];

    const load = (s) => new Promise((resolve) => {
        if (document.getElementById(s.id)) return resolve();
        const script = document.createElement('script');
        script.id = s.id;
        script.src = s.src;
        script.onload = resolve;
        document.head.appendChild(script);
    });

    await Promise.all(scripts.map(load));
}

await loadDependencies();

// 1. Initialize Global Database
window.db = new Dexie("PWOChatDB");
window.db.version(5).stores({
    messages: '++id, server_id, sender_id, message, d_created, status'
});

window.db.open().then(() => {
    console.log("PWOChatDB (v5) is active and consistent.");
}).catch(err => {
    console.error("DB failed to open:", err);
});

const db = window.db;

// Function to load local history immediately
async function loadLocalHistory() {
    // Consistency check: use 'd_created' NOT 'timestamp'
    const localMsgs = await window.db.messages.orderBy('d_created').toArray();
    
    if (localMsgs.length > 0) {
        const chatBox = document.getElementById('chat-box');
        chatBox.innerHTML = ''; 
        localMsgs.forEach(m => render(m, false));
    }
}

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

const soundIn = new Audio("assets/public/2354-preview.mp3");
const soundOut = new Audio("assets/public//2358-preview.mp3");

const SOCKET_URL = window.location.protocol === 'https:' ? 
    `wss://${window.location.hostname}/pwo/wss` : `ws://${window.location.hostname}:8080`;

const ws = new WSClient(SOCKET_URL, Auth.getToken() || "");
const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

let unreadCount = 0;
const originalTitle = document.title;

function updateTabTitle(reset = false) {
    if (reset) {
        unreadCount = 0;
        document.title = originalTitle;
    } else {
        unreadCount++;
        document.title = `(${unreadCount}) New Message! - ${originalTitle}`;
    }
}

window.addEventListener('focus', () => updateTabTitle(true));
window.addEventListener('click', () => {
    if (win.style.display === 'flex') updateTabTitle(true);
});

function startLogic() {
    const emojiBtn = document.getElementById('pwo-emoji-btn');
    
    if (emojiBtn) {
        console.log("UI Ready. Wiring logic...");
        
        initEmojiPicker();
        initExportHandler();
        initAutoExpand();
        initDragAndDrop(state); // Now 'state' is safe to use
		initLightboxHandler();
        
        initDeleteHandler(ws);  // Now 'ws' is safe to use
        initSearchHandler();
    } else {
        setTimeout(startLogic, 100);
    }
	
	// 1. Initialize once the DOM is ready
	const searchMgr = new ChatSearch('chat-box', 'search-nav', 'search-count');

	// 2. Link your Search Input (Trigger search while typing)
	document.getElementById('pwo-search-input')?.addEventListener('input', (e) => {
	    searchMgr.perform(e.target.value);
	});

	// 3. Link Navigation Buttons
	document.getElementById('btn-prev')?.addEventListener('click', () => {
	    searchMgr.navigate(-1);
	});

	document.getElementById('btn-next')?.addEventListener('click', () => {
	    searchMgr.navigate(1);
	});

	// 4. Link Close Button
	document.getElementById('btn-close-search')?.addEventListener('click', () => {
	    const input = document.getElementById('pwo-search-input');
	    if (input) input.value = '';
	    searchMgr.perform(''); // Resets and hides UI
	});	
	window.addEventListener('pwo_open_image', (e) => {
	    const lightbox = document.getElementById('pwo-lightbox');
	    const lightboxImg = document.getElementById('pwo-lightbox-img');
	    const downloadLink = document.getElementById('pwo-lightbox-download');
	    
	    if (lightbox && lightboxImg && downloadLink) {
	        const imageUrl = e.detail;
	        lightboxImg.src = imageUrl;
	        downloadLink.href = imageUrl; // Set the download URL
	        
	        lightbox.classList.remove('hidden');
	        lightbox.style.display = 'flex';
	    }
	});

	// Close button logic specifically
	document.getElementById('pwo-lightbox-close').onclick = () => {
	    document.getElementById('pwo-lightbox').classList.add('hidden');
	    document.getElementById('pwo-lightbox').style.display = 'none';
	};
	
	const lightbox = document.getElementById('pwo-lightbox');
	const downloadBtn = document.getElementById('pwo-lightbox-download');

	if (lightbox && downloadBtn) {
	    lightbox.addEventListener('click', (e) => {
	        // Only close if clicking the dark background or the close 'X' button
	        if (e.target === lightbox || e.target.closest('#pwo-lightbox-close')) {
	            lightbox.classList.add('hidden');
	            lightbox.style.display = 'none';
	        }
	    });

	    downloadBtn.addEventListener('click', (e) => {
	        // This stops the click from "bubbling" up to the lightbox background
	        e.stopPropagation(); 
	    });
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
	
	if (Notification.permission === "default") {
		Notification.requestPermission();
	}
		
    if (isHidden) {
        // This starts all your Task 2 & 3 logic once correctly
        startLogic();
		loadLocalHistory(); 
		       
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

window.addEventListener('ws_new_message', async (e) => {
    const data = e.detail.data || e.detail;
    const myId = localStorage.getItem('pwoUserId');

    const uiData = {
        ...data,
        server_id: data.id || data.server_id,
        is_me: data.is_me || (myId && data.sender_id && myId == data.sender_id)
    };

    // Commit to Dexie
    await window.db.messages.put({
        server_id: uiData.server_id,            
        sender_id: uiData.sender_id,     
        message: uiData.message,         
        d_created: uiData.d_created || new Date().toISOString(), 
        status: uiData.status ?? 1,
        file_path: uiData.file_path || null,
        file_name: uiData.file_name || null
    });

    // Render from the validated UI object
    render(uiData, true, false);

    document.getElementById('pwo-typing')?.classList.add('hidden');
    (uiData.is_me ? soundOut : soundIn).play().catch(() => {});
});

window.addEventListener('ws_chat_history', async (e) => {
    const history = e.detail.data || e.detail || [];
    
    await db.messages.clear();

    if (history.length > 0) {
        const dbEntries = history.map(m => ({
            server_id: m.id,            
            sender_id: m.sender_id,     
            message: m.message,         
            d_created: m.d_created || new Date().toISOString(), 
            status: m.status ?? 1,      
            file_path: m.file_path || null,
            file_name: m.file_name || null
        }));

        await db.messages.bulkAdd(dbEntries);
    }
    
    loadLocalHistory();
});

window.addEventListener('ws_message_read', () => {
    document.querySelectorAll('.msg-status').forEach(el => {
        el.innerText = '✓✓'; 
        el.parentElement.classList.replace('text-gray-400', 'text-sky-400');
    });
});

window.addEventListener('ws_message_deleted', async (e) => {
    const data = e.detail.data || e.detail;
    const msgId = data.id || data.server_id;

    if (msgId) {
        await window.db.messages.where('server_id').equals(msgId).delete();
        
        const element = document.querySelector(`[data-msg-id="${msgId}"]`);
        if (element) {
            element.classList.add('opacity-0', 'scale-95'); // Smooth fade out
            setTimeout(() => element.remove(), 300);
        }
    }
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
