import WSClient from '../public/wsclient.js';
import { parseMarkdown } from '../public/pwo-ui.js';
import { Auth } from '../public/pwo-auth.js';

const servername = window.location.protocol +'//'+ window.location.hostname +'/pwo/myapp/';

class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.msgFlow = document.getElementById('message-flow');
        this.ticketList = document.getElementById('chat-list');
        
        // Initialize WebSocket with your existing client logic
        this.ws = new WSClient(`ws://${window.location.hostname}:8080`, 'pwoToken');
        
        this.init();
    }

    async init() {
        this.ws.connect();
        this.attachEventListeners();
        await this.loadTickets(); // Initial sidebar load
    }

    attachEventListeners() {
        // --- WebSocket Events ---
		// Inside your admin-support.js constructor/init
		window.addEventListener('ws_message', (e) => {
		    const msg = e.detail;

		    // 1. If this message is from the customer I am currently looking at
		    if (msg.sender_id == this.activeUserId) {
		        this.renderBubble(msg, 'user');
		        this.scrollToBottom();
		        
		        // Tell the server I've read it (triggers customer checkmarks)
		        this.ws.socket.send(JSON.stringify({ 
		            type: 'message_read', 
		            target_id: msg.sender_id 
		        }));
		    }

		    // 2. Always refresh the sidebar list so the active user jumps to the top
		    this.loadTickets(); 
		});
		
        window.addEventListener('ws_typing', (e) => {
            if (e.detail.sender_id == this.activeUserId) {
                const indicator = document.getElementById('pwo-typing');
                indicator?.classList.remove('hidden');
                clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => indicator?.classList.add('hidden'), 3000);
            }
        });

        // --- UI Events ---
        document.getElementById('send-btn').onclick = () => this.handleSend();
        document.getElementById('admin-input').onkeypress = (e) => {
            if (e.key === 'Enter') this.handleSend();
        };
    }

	// Inside admin-support.js -> loadTickets()
	async loadTickets() {
	    try {
	        const res = await fetch(servername +'api/supportsystem/gettickets');
	        const response = await res.json();
	        
	        // Match your architecture: code 0 means success
	        if (response.code === 0) {
	            // NOTE THE DOUBLE .data.tickets based on your XHR output
	            const ticketsObj = response.data.tickets; 
	            
	            // Your json() method uses JSON_FORCE_OBJECT, so tickets might be an Object {0: {...}}
	            // We convert it to an array so we can map it
	            const ticketsArray = Object.values(ticketsObj);
	            
	            this.renderSidebar(ticketsArray);
	        }
	    } catch (err) {
	        console.error("Sidebar Sync Error:", err);
	    }
	}
	
    renderSidebar(tickets) {
        this.ticketList.innerHTML = tickets.map(t => `
            <div onclick="AdminApp.selectUser(${t.user_id})" 
                 class="p-4 rounded-xl cursor-pointer transition-all border mb-2 ${this.activeUserId == t.user_id ? 'bg-[#4d7cfe]/10 border-[#4d7cfe]/30' : 'bg-black/20 border-white/5 hover:bg-white/5'}">
                <div class="flex justify-between items-start mb-1">
                    <span class="text-xs font-black text-white uppercase tracking-wider">${t.realname}</span>
                    <span class="text-[9px] text-gray-500 font-bold">${this.formatTime(t.d_created)}</span>
                </div>
                <div class="flex justify-between items-center">
                    <p class="text-[11px] text-gray-500 truncate pr-4">${t.last_msg || 'File attachment'}</p>
                    ${t.unread_count > 0 ? `<span class="bg-blue-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded-md">${t.unread_count}</span>` : ''}
                </div>
            </div>
        `).join('');
    }

	// admin-support.js around line 105
	async selectUser(userId) {
	    this.activeUserId = userId;
	    
	    try {
	        const res = await fetch(servername +`api/supportsystem/getmessages?user_id=${userId}`);
	        const response = await res.json();
	        
	        // Match your architecture: code 0 is success
	        if (response.code === 0) {
	            this.msgFlow.innerHTML = '';
	            
	            // Access the nested 'messages' key
	            // Use Object.values() because JSON_FORCE_OBJECT turns arrays into {0:..., 1:...}
	            const messages = Object.values(response.data.messages);
	            
	            if (messages && messages.length > 0) {
	                messages.forEach(m => {
	                    // Determine role: if sender is NOT an admin/superadmin, it's a 'user' bubble
	                    const role = (m.perms === 'superadmin' || m.perms === 'admin') ? 'admin' : 'user';
	                    this.renderBubble(m, role);
	                });
	            } else {
	                this.msgFlow.innerHTML = '<div class="p-10 text-center text-gray-500 text-xs">No messages yet.</div>';
	            }
	        }
	    } catch (err) {
	        console.error("Error loading conversation:", err);
	    }
	}
	
    async handleSend() {
        const input = document.getElementById('admin-input');
        const text = input.value.trim();
        if (!text || !this.activeUserId) return;

        const payload = {
            type: 'message',
            target_id: this.activeUserId,
            message: text,
            sender_id: Auth.getUserId()
        };

        // Send via WebSocket (Real-time)
        this.ws.socket.send(JSON.stringify(payload));

        // UI Update
        this.renderBubble({ message: text, d_created: new Date().toISOString() }, 'admin');
        input.value = '';
    }

    renderBubble(msg, role) {
        const isMe = role === 'admin';
        const content = parseMarkdown(msg.message);
        
        const html = `
            <div class="flex flex-col ${isMe ? 'items-end' : 'items-start'} mb-4">
                <div class="${isMe ? 'bg-[#4d7cfe]' : 'bg-[#1b222b] border border-white/5'} p-4 rounded-2xl ${isMe ? 'rounded-tr-none' : 'rounded-tl-none'} shadow-xl max-w-[85%]">
                    <div class="text-[12px] ${isMe ? 'text-white' : 'text-gray-300'} leading-relaxed">${content}</div>
                </div>
                <span class="text-[9px] text-gray-600 font-bold uppercase mt-1 px-1">
                    ${this.formatTime(msg.d_created)}
                </span>
            </div>
        `;
        this.msgFlow.insertAdjacentHTML('beforeend', html);
        this.msgFlow.scrollTop = this.msgFlow.scrollHeight;
    }

    formatTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    markAsRead(userId) {
        // Send read signal to customer so they see checkmarks update
        this.ws.socket.send(JSON.stringify({ type: 'message_read', target_id: userId }));
    }
}

// Initialize globally so sidebar onclick can find it
window.AdminApp = new AdminSupport();
