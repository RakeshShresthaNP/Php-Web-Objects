import WSClient from '../public/wsclient.js';
import { parseMarkdown } from '../public/pwo-ui.js';
import { Auth } from '../public/pwo-auth.js';
import { 
    handleSend, 
    handleFile, 
    handleMic, 
    initAutoExpand, 
    initEmojiPicker 
} from '../public/pwo-logic.js';

const servername = window.location.protocol + '//' + window.location.hostname + '/pwo/myapp/';

class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.state = { activeUserId: null, pendingFile: null };

        // --- MAP TO YOUR HTML IDs ---
        this.ui = {
            flow:    document.getElementById('chat-box'),
            list:    document.getElementById('chat-list'),
            input:   document.getElementById('chat-in'),
            sendBtn: document.getElementById('chat-send'),
            fileIn:  document.getElementById('pwo-file-input'),
            attach:  document.getElementById('pwo-attach'),
            mic:     document.getElementById('pwo-mic')
        };
        
        const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
        this.ws = new WSClient(`${protocol}://${window.location.hostname}:8080`, 'pwoToken');
        
        this.init();
    }

    async init() {
        this.ws.connect();
        initAutoExpand();
        initEmojiPicker();
        this.attachEventListeners();
        await this.loadTickets();
    }

    attachEventListeners() {
        // Websocket Updates
        window.addEventListener('ws_new_message', (e) => {
            const msg = e.detail;
            const myId = Auth.getUserId();
            // If the message belongs to the current open chat
            if (this.activeUserId && (parseInt(msg.sender_id) === parseInt(this.activeUserId) || parseInt(msg.sender_id) === parseInt(myId))) {
                this.renderBubble(msg, parseInt(msg.sender_id) === parseInt(myId) ? 'admin' : 'user');
                this.scrollToBottom();
            }
            this.loadTickets(); // Refresh sidebar
        });

        // Click Attach Icon -> Triggers Hidden File Input
        if (this.ui.attach) this.ui.attach.onclick = () => this.ui.fileIn.click();
        
        // File Selection
        if (this.ui.fileIn) this.ui.fileIn.onchange = (e) => handleFile(e.target.files[0], this.state);
        
        // Mic / Voice
        if (this.ui.mic) this.ui.mic.onclick = () => handleMic(this.ws, this.state);

        // Send Click
        if (this.ui.sendBtn) {
            this.ui.sendBtn.onclick = () => {
                this.state.activeUserId = this.activeUserId;
                handleSend(this.state, this.ws);
            };
        }

        // Enter Key
        if (this.ui.input) {
            this.ui.input.onkeypress = (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.state.activeUserId = this.activeUserId;
                    handleSend(this.state, this.ws);
                }
            };
        }
    }

    async selectUser(userId) {
        this.activeUserId = userId;
        this.state.activeUserId = userId;

        try {
            const res = await fetch(`${servername}api/support/getmessages?user_id=${userId}`, {
                headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
            });
            const response = await res.json();
            
            if (response.code === 200 && this.ui.flow) {
                this.ui.flow.innerHTML = ''; // Clears the SVG placeholder
                
                const messages = response.data.messages ? Object.values(response.data.messages) : [];
                messages.forEach(m => {
                    const role = (parseInt(m.sender_id) === parseInt(Auth.getUserId())) ? 'admin' : 'user';
                    this.renderBubble(m, role);
                });
                
                this.scrollToBottom();
                this.ws.call('chat', 'markread', { target_user_id: parseInt(userId), token: Auth.getToken() });
            }
        } catch (err) { console.error("Message Load Error:", err); }
    }

    async loadTickets() {
        try {
            const res = await fetch(`${servername}api/support/gettickets`, {
                headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
            });
            const response = await res.json();
            if (response.code === 200) {
                this.renderSidebar(Object.values(response.data.tickets || {}));
            }
        } catch (err) { console.error("Sidebar Load Error:", err); }
    }

    renderSidebar(tickets) {
        if (!this.ui.list) return;
        this.ui.list.innerHTML = tickets.map(t => {
            const userId = t.sender_id || t.user_id;
            const isActive = parseInt(this.activeUserId) === parseInt(userId);
            return `
                <div onclick="AdminApp.selectUser(${userId})" 
                     class="p-4 rounded-xl cursor-pointer mb-2 border transition-all ${isActive ? 'bg-blue-500/10 border-blue-500/50' : 'bg-white/5 border-transparent hover:bg-white/10'}">
                    <div class="flex justify-between text-white text-[11px] font-bold uppercase tracking-tight">
                        <span>${t.realname || 'User ' + userId}</span>
                        <span class="opacity-40 text-[9px]">${this.formatTime(t.created_at)}</span>
                    </div>
                    <p class="text-[10px] text-gray-400 truncate mt-1">${t.message || '...'}</p>
                </div>`;
        }).join('');
    }

    renderBubble(msg, role) {
        if (!this.ui.flow) return;
        const isMe = role === 'admin';
        const bubble = `
            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} mb-4">
                <div class="max-w-[85%] p-3 px-4 rounded-2xl ${isMe ? 'bg-[#4d7cfe] text-white rounded-tr-none' : 'bg-[#1b222b] border border-white/10 text-gray-200 rounded-tl-none'} shadow-lg">
                    ${msg.file_path ? `<img src="${msg.file_path}" class="rounded-lg mb-2 max-w-full">` : ''}
                    <div class="text-[13px] leading-relaxed">${parseMarkdown(msg.message || '')}</div>
                    <div class="text-[9px] opacity-40 mt-1 font-bold ${isMe ? 'text-right' : 'text-left'}">
                        ${this.formatTime(msg.created_at)}
                    </div>
                </div>
            </div>`;
        this.ui.flow.insertAdjacentHTML('beforeend', bubble);
    }

    scrollToBottom() { if(this.ui.flow) this.ui.flow.scrollTop = this.ui.flow.scrollHeight; }

    formatTime(d) {
        if (!d) return '';
        const date = new Date(d.replace(/-/g, '/'));
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
}

window.AdminApp = new AdminSupport();