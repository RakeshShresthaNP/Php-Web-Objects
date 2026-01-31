import WSClient from '../public/wsclient.js';
import { parseMarkdown } from '../public/pwo-ui.js';
import { Auth } from '../public/pwo-auth.js';

const servername = window.location.protocol + '//' + window.location.hostname + '/pwo/myapp/';

class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.msgFlow = document.getElementById('message-flow');
        this.ticketList = document.getElementById('chat-list');
        // Ensure ws protocol matches environment (ws vs wss)
        const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
        this.ws = new WSClient(`${protocol}://${window.location.hostname}:8080`, 'pwoToken');
        this.init();
    }

    async init() {
        this.ws.connect();
        this.attachEventListeners();
        await this.loadTickets();
    }

    getAuthHeaders() {
        return { 
            'X-Forwarded-Host': window.location.hostname,
            'Authorization': `Bearer ${Auth.getToken()}`
        };
    }

    attachEventListeners() {
        // Listen for new messages via WebSocket
        window.addEventListener('ws_new_message', async (e) => {
            const msg = e.detail;
            await this.loadTickets(); // Refresh sidebar to show latest snippet/unread count
            
            const myId = Auth.getUserId();
            const isFromCurrentTarget = parseInt(msg.sender_id) === parseInt(this.activeUserId);
            const isFromMe = parseInt(msg.sender_id) === parseInt(myId);

            if (this.activeUserId && (isFromCurrentTarget || isFromMe)) {
                // Check if message already exists in DOM (to prevent double rendering from optimistic UI)
                const existing = msg.id ? document.querySelector(`[data-id="${msg.id}"]`) : null;
                if (!existing) {
                    this.renderBubble(msg, isFromMe ? 'admin' : 'user');
                    this.scrollToBottom();
                }
            }
        });

        // UI Event Listeners
        const sendBtn = document.getElementById('send-btn');
        if (sendBtn) sendBtn.onclick = () => this.handleSend();
        
        const input = document.getElementById('admin-input');
        if (input) {
            input.onkeypress = (e) => { 
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.handleSend();
                }
            };
        }
    }

    async selectUser(userId) {
        if (this.activeUserId === userId) return;
        this.activeUserId = userId;
        
        try {
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
                
                // Mark messages as read via WebSocket
                this.ws.call('chat', 'markread', { 
                    target_user_id: parseInt(userId),
                    token: Auth.getToken() 
                }, this.getAuthHeaders());

                await this.loadTickets(); 
            }
        } catch (err) { console.error("Failed to load messages:", err); }
    }

    async handleSend() {
        const input = document.getElementById('admin-input');
        const txt = input.value.trim();
        const token = Auth.getToken();

        if (!this.activeUserId || !txt || !token) return;

        // 1. Send via WebSocket
        this.ws.call('chat', 'send', {
            target_id: this.activeUserId,
            message: txt,
            token: token
        }, this.getAuthHeaders());

        // 2. Optimistic UI: Render immediately for the admin
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
                // Sort by most recent activity
                ticketsArray.sort((a, b) => new Date(b.d_created) - new Date(a.d_created));
                this.renderSidebar(ticketsArray);
            }
        } catch (err) { console.error("Ticket Load Error:", err); }
    }

    renderSidebar(tickets) {
        if (!this.ticketList) return;
        this.ticketList.innerHTML = tickets.map(t => {
            const userId = t.sender_id || t.user_id;
            const isActive = parseInt(this.activeUserId) === parseInt(userId);
            const snippet = t.message || (t.file_path ? '📎 Attachment' : '...');
            
            return `
                <div onclick="AdminApp.selectUser(${userId})" 
                     class="p-4 rounded-xl cursor-pointer transition-all border mb-2 ${isActive ? 'bg-[#4d7cfe]/10 border-[#4d7cfe]/30' : 'bg-black/20 border-white/5 hover:bg-white/5'}">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-xs font-black text-white uppercase tracking-wider">${t.realname || 'User #' + userId}</span>
                        <span class="text-[9px] text-gray-500 font-bold">${this.formatTime(t.d_created)}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-[11px] text-gray-500 truncate pr-4">${snippet}</p>
                        ${t.unread_count > 0 ? `<span class="bg-[#4d7cfe] text-white text-[9px] font-black px-1.5 py-0.5 rounded-md shadow-sm">${t.unread_count}</span>` : ''}
                    </div>
                </div>`;
        }).join('');
    }

	renderBubble(msg, role) {
	    // 1. Determine if it's the Admin (Right) or Customer (Left)
	    const isMe = role === 'admin';
	    
	    // 2. Classes for alignment and colors
	    // We use 'items-end' for Admin to push content to the right
	    const alignmentClass = isMe ? 'items-end' : 'items-start';
	    
	    // Bubble styling
	    const bubbleClass = isMe 
	        ? 'bg-[#4d7cfe] text-white rounded-2xl rounded-tr-none' // Admin Blue
	        : 'bg-[#1b222b] border border-white/10 text-gray-200 rounded-2xl rounded-tl-none'; // Customer Dark

	    let mediaHtml = '';
	    if (msg.file_path) {
	        // ... (your existing media logic)
	        mediaHtml = `<img src="${msg.file_path}" class="rounded-lg mb-2 max-w-xs">`;
	    }

	    const html = `
	        <div class="flex flex-col ${alignmentClass} w-full mb-2">
	            <div class="flex flex-col ${alignmentClass} max-w-[80%]">
	                <div class="${bubbleClass} p-3 px-4 shadow-md">
	                    ${mediaHtml}
	                    <div class="text-[13px] leading-relaxed break-words">
	                        ${parseMarkdown(msg.message || '')}
	                    </div>
	                    <div class="text-[9px] opacity-50 mt-1 font-medium ${isMe ? 'text-right' : 'text-left'}">
	                        ${this.formatTime(msg.d_created)}
	                    </div>
	                </div>
	            </div>
	        </div>`;
	        
	    this.msgFlow.insertAdjacentHTML('beforeend', html);
	    this.scrollToBottom();
	}
		
    scrollToBottom() { 
        this.msgFlow.scrollTo({ 
            top: this.msgFlow.scrollHeight, 
            behavior: 'smooth' 
        }); 
    }

    formatTime(d) { 
        if(!d) return '';
        try {
            // Support for various date formats including SQL timestamps
            const date = new Date(d.replace(/-/g, '/'));
            return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); 
        } catch(e) { return ''; }
    }
}

// Instantiate globally for onclick handlers in the sidebar
window.AdminApp = new AdminSupport();