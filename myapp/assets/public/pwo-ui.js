/**
 * pwo-ui.js
 * Extended to support database file_path and PDF/WebP display
 */
const tempP = document.createElement('p');

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

    const msgId = data.server_id || data.id || (data.data && data.data.id);
    
    const myId = localStorage.getItem('pwoUserId')?.toString();
    const senderId = data.sender_id?.toString();
    
    const isMe = data.is_me === true || (myId && senderId && myId === senderId);
			
	const isSystem = data.system == 1 || data.system === true;
	
    const deleteBtn = (isMe && msgId) ? `
        <button class="pwo-delete-btn absolute bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md z-50 border border-white transition-transform hover:scale-110" 
                data-id="${msgId}" 
                style="top: -8px; right: -8px; display: flex !important;">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    ` : '';

    // CONTENT BUILDER (Files, Images, Voice, Text)
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
                contentHTML = `<a href="${fileUrl}" target="_blank" class="flex items-center gap-2 p-2 bg-black/5 rounded-lg mb-2 text-blue-600 underline text-xs">
                    <span class="truncate max-w-[150px]">${data.file_name || 'View Attachment'}</span>
                </a>`;
            }
        }
		if (data.message) {
		    // Create a temporary element to safely escape the string
		    tempP.textContent = data.message; 
		    const safeMessage = tempP.innerHTML; // Browser handles the escaping perfectly

		    contentHTML += `<p class="message-text leading-relaxed whitespace-pre-wrap text-sm">${safeMessage}</p>`;
		}
	}

    // UI ASSEMBLY
    const div = document.createElement('div');
    
    // Tag the element for easy reference (useful for the delete handler)
    if (msgId) div.setAttribute('data-msg-id', msgId);

    if (isSystem) {
        div.className = "flex justify-center my-6 px-4 w-full";
        div.innerHTML = `
            <div class="bg-gray-100 text-gray-500 text-[11px] font-semibold uppercase tracking-widest py-1.5 px-4 rounded-full border border-gray-200">
                ${data.message}
            </div>`;
    } else {
        // Time formatting
        const dateObj = data.d_created ? new Date(data.d_created) : new Date();
        const msgTime = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });

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
				    
    // AUTO-SCROLL
    // Only scroll if this is a new live message, not during history load
    if (isNew) {
        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
    }
}
