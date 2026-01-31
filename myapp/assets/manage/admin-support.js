import WSClient from '../public/wsclient.js';
import { parseMarkdown } from '../public/pwo-ui.js';
import { Auth } from '../public/pwo-auth.js';

const servername = window.location.protocol + '//' + window.location.hostname + '/pwo/myapp/';

class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.msgFlow = document.getElementById('message-flow');
        this.ticketList = document.getElementById('chat-list');
        this.ws = new WSClient(`ws://${window.location.hostname}:8080`, 'pwoToken');
        this.init();
    }

    async init() {
        this.ws.connect();
        this.attachEventListeners();
        await this.loadTickets();
    }

    getAuthHeaders() {
        return { 'X-Forwarded-Host': window.location.hostname };
    }

    attachEventListeners() {
        window.addEventListener('ws_new_message', async (e) => {
            const msg = e.detail;
            await this.loadTickets();
            const isFromCurrentTarget = parseInt(msg.sender_id) === parseInt(this.activeUserId);
            const isFromMe = parseInt(msg.sender_id) === parseInt(Auth.getUserId());

            if (this.activeUserId && (isFromCurrentTarget || isFromMe)) {
                if (!document.querySelector(`[data-id="${msg.id}"]`)) {
                    this.renderBubble(msg, isFromMe ? 'admin' : 'user');
                    this.scrollToBottom();
                }
            }
        });

        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) sendBtn.onclick = () => this.handleSend();
        const input = document.getElementById('admin-input');
        if (input) input.onkeypress = (e) => { if (e.key === 'Enter') this.handleSend(); };
    }

    async selectUser(userId) {
        this.activeUserId = userId;
        const res = await fetch(`${servername}api/supportsystem/getmessages?user_id=${userId}`, {
            headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
        });
        const response = await res.json();
        
        if (response.code === 200) {
            this.msgFlow.innerHTML = '';
            const messages = response.data.messages ? Object.values(response.data.messages) : [];
            messages.forEach(m => {
                const role = (parseInt(m.sender_id) === parseInt(Auth.getUserId())) ? 'admin' : 'user';
                this.renderBubble(m, role);
            });
            this.scrollToBottom();
            
            // CONSISTENT CALL: controller, method, params, headers
            this.ws.call('chat', 'markread', { 
                target_user_id: parseInt(userId),
                token: localStorage.getItem('pwoToken') 
            }, this.getAuthHeaders());

            await this.loadTickets(); 
        }
    }

    async handleSend() {
        const input = document.getElementById('admin-input');
        const txt = input.value.trim();
        const t = localStorage.getItem('pwoToken');

        if (!this.activeUserId || !txt || !t) return;

        // CONSISTENT CALL: controller, method, params, headers
        this.ws.call('chat', 'send', {
            target_id: this.activeUserId,
            message: txt,
            token: t
        }, this.getAuthHeaders());

        this.renderBubble({
            message: txt,
            sender_id: Auth.getUserId(),
            d_created: new Date().toISOString()
        }, 'admin');
        
        input.value = '';
        this.scrollToBottom();
    }

    async loadTickets() {
        try {
            const res = await fetch(`${servername}api/supportsystem/gettickets?nocache=${Date.now()}`, {
                headers: { 'Authorization': `Bearer ${Auth.getToken()}` }
            });
            const response = await res.json();
            if (response.code === 200) {
                const ticketsArray = Object.values(response.data.tickets || {});
                ticketsArray.sort((a, b) => new Date(b.d_created) - new Date(a.d_created));
                this.renderSidebar(ticketsArray);
            }
        } catch (err) { console.error(err); }
    }

    renderSidebar(tickets) {
        if (!this.ticketList) return;
        this.ticketList.innerHTML = tickets.map(t => {
            const userId = t.sender_id || t.user_id;
            const isActive = parseInt(this.activeUserId) === parseInt(userId);
            return `
                <div onclick="AdminApp.selectUser(${userId})" 
                     class="p-4 rounded-xl cursor-pointer transition-all border mb-2 ${isActive ? 'bg-[#4d7cfe]/10 border-[#4d7cfe]/30' : 'bg-black/20 border-white/5 hover:bg-white/5'}">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-xs font-black text-white uppercase tracking-wider">${t.realname || 'User #' + userId}</span>
                        <span class="text-[9px] text-gray-500 font-bold">${this.formatTime(t.d_created)}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-[11px] text-gray-500 truncate pr-4">${t.message || '...'}</p>
                        ${t.unread_count > 0 ? `<span class="bg-[#4d7cfe] text-white text-[9px] font-black px-1.5 py-0.5 rounded-md">${t.unread_count}</span>` : ''}
                    </div>
                </div>`;
        }).join('');
    }

    renderBubble(msg, role) {
        const isMe = role === 'admin';
        const html = `
            <div data-id="${msg.id || ''}" class="flex flex-col ${isMe ? 'items-end' : 'items-start'} mb-4">
                <div class="${isMe ? 'bg-[#4d7cfe]' : 'bg-[#1b222b] border border-white/5'} p-4 rounded-2xl max-w-[85%]">
                    <div class="text-[12px] ${isMe ? 'text-white' : 'text-gray-300'}">${parseMarkdown(msg.message || '')}</div>
                </div>
                <span class="text-[9px] text-gray-600 mt-1">${this.formatTime(msg.d_created)}</span>
            </div>`;
        this.msgFlow.insertAdjacentHTML('beforeend', html);
    }

    scrollToBottom() { this.msgFlow.scrollTop = this.msgFlow.scrollHeight; }
    formatTime(d) { 
        if(!d) return '';
        const date = new Date(d.replace(/-/g, '/'));
        return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); 
    }
}

window.AdminApp = new AdminSupport();