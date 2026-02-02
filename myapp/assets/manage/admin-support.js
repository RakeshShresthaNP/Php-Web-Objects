import WSClient from '../public/wsclient.js';
import { render } from '../public/pwo-ui.js';
import { Auth } from '../public/pwo-auth.js';
import { 
    handleSend, 
    handleFile, 
    handleMic, 
    initAutoExpand, 
    initEmojiPicker,
	initDeleteHandler
} from '../public/pwo-logic.js';

const servername = window.location.protocol + '//' + window.location.hostname + '/pwo/myapp/';

class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.state = { activeUserId: null, pendingFile: null, tickets: [] };

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
        const wsUrl = `${protocol}://${window.location.hostname}:8080`;
        
        this.ws = new WSClient(wsUrl, 'pwoToken');
        this.init();
    }

    async init() {
        this.ws.connect();

        setTimeout(() => {
            if (this.ws) {
                this.ws.call('chat', 'history', { token: Auth.getToken() });
            }
        }, 500);
                    
        initAutoExpand();
        initEmojiPicker();
		initDeleteHandler(this.ws);
        this.attachEventListeners();
        await this.loadTickets();
    }

    attachEventListeners() {
        const handleIncoming = (e) => {
            this.processIncoming(e.detail);
        };
        window.addEventListener('ws_message', handleIncoming);
        window.addEventListener('ws_new_message', handleIncoming);

        if (this.ui.attach) {
            this.ui.attach.onclick = () => this.ui.fileIn.click();
        }
		
        if (this.ui.fileIn) {
            this.ui.fileIn.onchange = (e) => {
                if (e.target.files[0]) {
                    handleFile(e.target.files[0], this.state);
                }
            };
        }

        if (this.ui.mic) {
            this.ui.mic.onclick = () => handleMic(this.state);
        }

        const executeSend = async () => {
            this.state.activeUserId = this.activeUserId;
            await handleSend(this.state, this.ws);
            this.clearPreview();
        };

        if (this.ui.sendBtn) this.ui.sendBtn.onclick = executeSend;

        if (this.ui.input) {
            this.ui.input.onkeypress = (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    executeSend();
                }
            };
        }
    }

    processIncoming(msg) {
        const myId = Number(Auth.getUserId());
        const incomingSenderId = Number(msg.sender_id || msg.user_id);
        const targetId = Number(msg.target_id || msg.target_user_id);
        const currentActiveId = this.activeUserId ? Number(this.activeUserId) : null;

        const isMe = incomingSenderId === myId;
        const customerId = isMe ? targetId : incomingSenderId;

        if (!customerId) return;

        if (currentActiveId === customerId) {
            render(msg, isMe ? 'admin' : 'user');
            this.scrollToBottom();
        }

        const ticketIndex = this.state.tickets.findIndex(t => 
            Number(t.user_id || t.sender_id) === customerId
        );

        const previewText = msg.message || (msg.file_path ? '📁 Attachment' : '...');

        if (ticketIndex !== -1) {
            const updatedTicket = this.state.tickets.splice(ticketIndex, 1)[0];
            updatedTicket.message = previewText;
            updatedTicket.created_at = msg.created_at || new Date().toISOString();
            
            if (!isMe && currentActiveId !== customerId) {
                updatedTicket.is_read = 0;
            }
            
            this.state.tickets.unshift(updatedTicket); // Move to top
        } else {
            this.state.tickets.unshift({
                user_id: customerId,
                realname: msg.sender_name || msg.realname || `Customer ${customerId}`,
                message: previewText,
                created_at: msg.created_at || new Date().toISOString(),
                is_read: isMe ? 1 : 0
            });
        }
        this.renderSidebar(this.state.tickets);
    }

    async loadTickets() {
        try {
            const res = await fetch(`${servername}api/support/gettickets`, {
                headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
            });
            const response = await res.json();
            if (response.code === 200) {
                this.state.tickets = Object.values(response.data.tickets || {});
                this.renderSidebar(this.state.tickets);
            }
        } catch (err) { console.error("❌ Sidebar Load Error:", err); }
    }

    renderSidebar(tickets) {
        if (!this.ui.list || !tickets) return;

        const sortedTickets = [...tickets].sort((a, b) => {
            return new Date(b.created_at) - new Date(a.created_at);
        });

        this.ui.list.innerHTML = sortedTickets.map(t => {
            const userId = t.user_id || t.sender_id;
            const myId = parseInt(Auth.getUserId());
            const isActive = parseInt(this.activeUserId) === parseInt(userId);
            const isUnread = parseInt(t.is_read) === 0 && parseInt(t.sender_id) !== myId;

            return `
                <div onclick="AdminApp.selectUser(${userId})" 
                     class="p-4 rounded-xl cursor-pointer mb-2 border transition-all relative
                     ${isActive ? 'bg-blue-500/10 border-blue-500/50' : 'bg-white/5 border-transparent hover:bg-white/10'}">
                    
                    ${isUnread ? '<div class="absolute right-4 top-1/2 -translate-y-1/2 w-2 h-2 bg-blue-500 rounded-full shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>' : ''}
                    
                    <div class="flex justify-between text-white text-[11px] font-bold uppercase tracking-tight pr-6">
                        <span class="truncate pr-2">${t.realname || 'Customer ' + userId}</span>
                        <span class="opacity-40 text-[9px] whitespace-nowrap">${this.formatTime(t.created_at)}</span>
                    </div>
                    <p class="text-[10px] ${isUnread ? 'text-gray-200 font-medium' : 'text-gray-400'} truncate mt-1">
                        ${t.message || (t.file_path ? '📁 Attachment' : '...')}
                    </p>
                </div>`;
        }).join('');
    }

    async selectUser(userId) {
        this.activeUserId = userId;
        this.state.activeUserId = userId;
        
        const ticket = this.state.tickets.find(t => Number(t.user_id || t.sender_id) === Number(userId));
        if (ticket) { 
            ticket.is_read = 1; 
            this.renderSidebar(this.state.tickets); 
        }

        try {
            const res = await fetch(`${servername}api/support/getmessages?user_id=${userId}`, {
                headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
            });
            const response = await res.json();
            if (response.code === 200 && this.ui.flow) {
                this.ui.flow.innerHTML = ''; 
                const messages = response.data.messages ? Object.values(response.data.messages) : [];
                messages.forEach(m => {
                    const role = (parseInt(m.sender_id) === parseInt(Auth.getUserId())) ? 'admin' : 'user';
                    render(m, role);
                });
                this.scrollToBottom();
                this.ws.call('chat', 'markread', { target_user_id: parseInt(userId), token: Auth.getToken() });
            }
        } catch (err) { console.error("Message Load Error:", err); }
    }

    scrollToBottom() { 
        if(this.ui.flow) this.ui.flow.scrollTop = this.ui.flow.scrollHeight; 
    }

    formatTime(d) {
        if (!d) return '';
        const date = new Date(d.replace(/-/g, '/'));
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    clearPreview() {
        const preview = document.getElementById('pwo-preview');
        if (preview) preview.classList.add('hidden');
        this.state.pendingFile = null;
        if (this.ui.fileIn) this.ui.fileIn.value = '';
    }
}

window.AdminApp = new AdminSupport();