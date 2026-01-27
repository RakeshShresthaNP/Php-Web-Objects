/**
 * pwo-ui.js
 * Extended to support database file_path and PDF/WebP display
 */

export function parseMarkdown(t) {
    if (!t) return '';
    return t
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`([^`]+)`/g, '<code class="bg-gray-100 px-1 rounded">$1</code>')
        .replace(/\n/g, '<br>');
}

export function render(data, isNew = true, isTemp = false) {
    const chatBox = document.getElementById('chat-box');
    if (!chatBox) return;

    // 1. IDENTIFICATION & CONSISTENCY
    // Map IDs: Server uses 'id', IndexedDB uses 'server_id'
    const msgId = data.id || data.server_id; 
    const myId = localStorage.getItem('pwoUserId');
    const senderId = data.sender_id?.toString() || null;
    
    // Check if message belongs to current user
    const isMe = data.is_me === true || (myId && senderId && myId == senderId);
    const isSystem = data.system == 1 || data.system === true;

    // 2. DELETE BUTTON LOGIC
    // Button only appears for 'isMe' and requires a valid msgId (id or server_id)
    const deleteBtn = (isMe && msgId) ? `
        <button class="pwo-delete-btn absolute bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md z-50 border border-white transition-transform hover:scale-110" 
                data-id="${msgId}" 
                style="top: -8px; right: -8px; display: flex !important; opacity: 1 !important; visibility: visible !important;">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    ` : '';

    // 3. CONTENT BUILDER (Files & Text)
    let contentHTML = '';
    if (!isSystem) {
        const fileUrl = data.localUrl || data.file_path;
        if (fileUrl) {
            const isVoice = data.file_name?.endsWith('.webm') || data.file_name?.endsWith('.wav');
            const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(data.file_name || '');

            if (isVoice) {
                contentHTML = `<div class="w-[220px] mb-1"><audio controls class="w-full h-8"><source src="${fileUrl}" type="audio/webm"></audio></div>`;
            } else if (isImage) {
                contentHTML = `<div class="mb-2">
                        <img src="${fileUrl}" 
                             class="pwo-chat-image rounded-lg max-w-full h-auto cursor-zoom-in border border-white/10 shadow-sm transition-opacity hover:opacity-90" 
                             onclick="window.dispatchEvent(new CustomEvent('pwo_open_image', {detail: '${fileUrl}'}))" />
                    </div>`;
            } else {
                contentHTML = `<a href="${fileUrl}" target="_blank" class="flex items-center gap-2 p-2 bg-black/5 rounded-lg mb-2 text-blue-600 underline"><span class="text-xs truncate max-w-[150px]">${data.file_name || 'View Attachment'}</span></a>`;
            }
        }
        if (data.message) contentHTML += `<p class="leading-relaxed whitespace-pre-wrap text-sm">${data.message}</p>`;
    }

    // 4. UI ASSEMBLY
    const div = document.createElement('div');
    const isAtBottom = chatBox.scrollHeight - chatBox.scrollTop <= chatBox.clientHeight + 100;
    
    if (isSystem) {
        div.className = "flex justify-center my-6 px-4 w-full";
        div.innerHTML = `
            <div class="bg-gray-100 text-gray-500 text-[11px] font-semibold uppercase tracking-widest py-1.5 px-4 rounded-full border border-gray-200 shadow-sm">
                ${data.message}
            </div>`;
    } else {
        // Handle time (Format: HH:mm)
        let msgTime = data.time;
        if (!msgTime) {
            const dateObj = data.d_created ? new Date(data.d_created) : new Date();
            msgTime = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
        }

        div.className = `mb-4 flex ${isMe ? 'justify-end pr-4' : 'justify-start items-end'}`; 
        if (isTemp) div.id = `temp-${data.temp_id}`;

        const avatarHTML = (!isMe) ? `
            <div class="w-8 h-8 rounded-full bg-slate-400 flex items-center justify-center shrink-0 mr-2 mb-1 border border-white">
                <span class="text-[10px] font-bold text-white uppercase">AI</span>
            </div>` : '';

        div.innerHTML = `
            ${avatarHTML}
            <div class="relative" style="width: fit-content; max-width: 85%; min-width: 100px;">
                <div class="${isMe ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-none'} p-3 shadow-sm"
                     style="position: relative; overflow: visible !important;">
                    ${deleteBtn}
                    ${contentHTML}
                    <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${isMe ? 'text-emerald-100' : 'text-gray-400'}">
                        <span>${msgTime}</span>
                        ${isMe ? `<span class="msg-status font-bold">${data.is_read ? '✓✓' : '✓'}</span>` : ''}
                    </div>
                </div>
            </div>`;
    }

    // Append to Chat Box
    chatBox.appendChild(div);
	
    // 5. DATABASE PERSISTENCE (Matches v5 Schema)
    // Only save if it's a "New" real message, not from history loader and not a temporary sending state
    if (isNew && window.db && !isTemp) {
        window.db.messages.add({
            server_id: msgId,             
            sender_id: senderId,            
            message: data.message || '',          
            d_created: data.d_created || new Date().toISOString(), 
            status: data.status ?? 1,
            file_path: data.file_path || null,
            file_name: data.file_name || null,
            system: isSystem ? 1 : 0        
        }).catch(e => console.error("IndexedDB Save Error:", e));
    }
				    
    // Auto-scroll logic
    if (isNew && isAtBottom) {
        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
    }
}
