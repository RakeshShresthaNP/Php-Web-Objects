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

    // 1. Get Me (from storage) and Sender (from message)
    const myId = localStorage.getItem('pwoUserId');
    const senderId = data.sender_id ? data.sender_id.toString() : null;
    
    // 2. FORCE a boolean true/false for isMe
    // We check if data.is_me is true OR if the IDs match
    const isMe = data.is_me === true || (myId && senderId && myId == senderId) ? true : false;

    // 3. Get the database ID
    const msgId = data.id;

    // DEBUG: Look for this in the console for your REAL messages
    console.log(`Msg: ${data.message} | ID: ${msgId} | isMe: ${isMe}`)
		
    // --- 2. DELETE BUTTON (Internal Placement - Cannot be clipped) ---
	const deleteBtn = (isMe && msgId) ? `
	    <button class="pwo-delete-btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-md z-50 border border-white transition-transform hover:scale-110" 
	            data-id="${msgId}" 
	            title="Delete Message"
	            style="display: flex !important; opacity: 1 !important; visibility: visible !important;">
	        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
	        </svg>
	    </button>
	` : '';
				
    // --- 3. TIME FORMATTING ---
	const msgTime = data.time ? data.time : 
	                   new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
					   
    // --- 4. CONTENT LOGIC (Files, Voice, Text) ---
	let contentHTML = '';
	if (data.file_path || data.localUrl) {
	    const fileUrl = data.localUrl || data.file_path;
	    const isVoice = data.file_name && data.file_name.endsWith('.webm');
	    const isImage = data.file_name && /\.(jpg|jpeg|png|gif|webp)$/i.test(data.file_name);

	    if (isVoice) {
			contentHTML = `
			    <div class="flex flex-col gap-1 min-w-[150px]"> <div class="flex items-center gap-1 text-[9px] opacity-75">
			            <svg class="w-3 h-3" ...></svg>
			            Voice Message
			        </div>
			        <audio controls class="w-full h-7">
			            <source src="${fileUrl}" type="audio/webm">
			        </audio>
			    </div>`;
		} else if (isImage) {
            contentHTML = `<div class="mb-2"><img src="${fileUrl}" class="rounded-lg max-w-full h-auto border border-white/10 shadow-sm" /></div>`;
        } else {
            contentHTML = `
                <a href="${fileUrl}" target="_blank" class="flex items-center gap-2 p-2 bg-black/5 rounded-lg hover:bg-black/10 transition-colors mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span class="text-xs font-medium truncate max-w-[150px]">${data.file_name || 'Download File'}</span>
                </a>`;
        }
    }

    if (data.message) {
        contentHTML += `<p class="leading-relaxed whitespace-pre-wrap">${data.message}</p>`;
    }

    // --- 5. ASSEMBLE HTML ROW ---
	const div = document.createElement('div');
    // Added pr-4 to ensure space even when scrollbars appear
    // Added items-end to force the bubble to stick to the right without stretching
    div.className = `flex flex-col mb-4 w-full pr-4 ${isMe ? 'items-end' : 'items-start'}`; 
    if (isTemp) div.id = `temp-${data.temp_id}`;
	
    div.innerHTML = `
        <div class="relative group" style="width: fit-content; max-width: 85%;">
            <div class="${isMe ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-none'} p-3 shadow-sm text-sm" 
                 style="width: fit-content; min-width: 80px; display: inline-block;">
                ${deleteBtn}
                ${contentHTML}
                
                <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${isMe ? 'text-emerald-100' : 'text-gray-400'}">
                    <span>${msgTime}</span>
                    ${isMe ? '<span class="msg-status font-bold">' + (data.is_read ? '✓✓' : '✓') + '</span>' : ''}
                </div>
            </div>
        </div>
    `;
														
    // --- 6. APPEND AND SCROLL ---
    chatBox.appendChild(div);
    if (isNew) chatBox.scrollTop = chatBox.scrollHeight;
}
