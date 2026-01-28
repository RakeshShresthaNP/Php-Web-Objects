class AdminSupport {
    constructor() {
        this.activeUserId = null;
        this.users = new Map(); // Stores user data and message history
        this.elements = {
            queue: document.getElementById('user-queue'),
            chatWindow: document.getElementById('admin-chat-window'),
            input: document.getElementById('admin-input'),
            userInfo: document.getElementById('active-user-info')
        };
        this.init();
    }

    init() {
        // Here we would link to the WebSocket Bridge
        console.log("Admin Support System Active.");
    }

    // Call this when the WebSocket receives a "new_user" event
    addUserToQueue(user) {
        this.users.set(user.id, { ...user, messages: [] });
        this.renderQueue();
    }

    renderQueue() {
        this.elements.queue.innerHTML = '';
        this.users.forEach((user, id) => {
            const card = document.createElement('div');
            card.className = `p-4 rounded-xl cursor-pointer transition ${this.activeUserId === id ? 'bg-blue-600 shadow-lg shadow-blue-500/20' : 'hover:bg-white/5 border border-white/5'}`;
            card.innerHTML = `
                <div class="flex justify-between items-start">
                    <span class="font-medium text-sm text-white">${user.name || 'Guest ' + id.slice(-4)}</span>
                    <span class="text-[10px] text-gray-400">12:45</span>
                </div>
                <p class="text-xs text-gray-400 truncate mt-1">${user.lastMessage || 'No messages yet'}</p>
            `;
            card.onclick = () => this.selectUser(id);
            this.elements.queue.appendChild(card);
        });
    }

    selectUser(id) {
        this.activeUserId = id;
        this.elements.userInfo.classList.remove('invisible');
        document.getElementById('chat-user-name').innerText = `User: ${id.slice(-8)}`;
        this.renderQueue(); // Update active styling
        this.loadMessages(id);
    }

    loadMessages(id) {
        this.elements.chatWindow.innerHTML = ''; // Clear and load from local Map or Dexie
        const user = this.users.get(id);
        user.messages.forEach(msg => this.appendMessage(msg));
    }

    appendMessage(msg) {
        const isMe = msg.sender === 'admin';
        const msgHtml = `
            <div class="flex ${isMe ? 'justify-end' : 'justify-start'} animate-fadeIn">
                <div class="max-w-[70%] p-4 rounded-2xl ${isMe ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-[#1c222d] text-gray-200 rounded-tl-none border border-white/5'}">
                    <p class="text-sm">${msg.text}</p>
                </div>
            </div>
        `;
        this.elements.chatWindow.insertAdjacentHTML('beforeend', msgHtml);
        this.elements.chatWindow.scrollTop = this.elements.chatWindow.scrollHeight;
    }
}

const SupportApp = new AdminSupport();