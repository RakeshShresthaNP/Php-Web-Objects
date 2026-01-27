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

    // 1. Create message container
    const div = document.createElement('div');
    div.className = `flex mb-4 ${data.is_me ? 'justify-end' : 'justify-start'}`;
    if (isTemp) div.id = `temp-${data.temp_id}`;

    // 2. Handle File/Voice Content
    let contentHTML = '';
    if (data.file_path || data.localUrl) {
        const fileUrl = data.localUrl || data.file_path;
        const isVoice = data.file_name && data.file_name.endsWith('.webm');
        const isImage = data.file_name && /\.(jpg|jpeg|png|gif|webp)$/i.test(data.file_name);

        if (isVoice) {
            contentHTML = `
                <div class="flex flex-col gap-2 min-w-[200px]">
                    <div class="flex items-center gap-2 text-[10px] opacity-70">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a2 2 0 012-2h6a2 2 0 012 2v12a2 2 0 01-2 2H9a2 2 0 01-2-2V4z"></path></svg>
                        Voice Message
                    </div>
                    <audio controls class="w-full h-8 accent-emerald-600">
                        <source src="${fileUrl}" type="audio/webm">
                    </audio>
                </div>`;
        } else if (isImage) {
            contentHTML = `<div class="mb-2"><img src="${fileUrl}" class="rounded-lg max-w-full h-auto border border-white/20 shadow-sm" /></div>`;
        } else {
            contentHTML = `
                <a href="${fileUrl}" target="_blank" class="flex items-center gap-2 p-2 bg-black/5 rounded-lg hover:bg-black/10 transition-colors mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span class="text-xs font-medium truncate max-w-[150px]">${data.file_name || 'Download File'}</span>
                </a>`;
        }
    }

    // 3. Handle Text Content
    if (data.message) {
        contentHTML += `<p class="leading-relaxed">${data.message}</p>`;
    }

    // 4. Status Icon (Pending vs Read)
    let statusIcon = '🕒'; 
    let statusColor = 'text-gray-400';
    if (!isTemp) {
        statusIcon = data.is_read ? '✓✓' : '✓';
        statusColor = data.is_read ? 'text-sky-400' : 'text-gray-400';
    }

    // 5. Trash Icon for Deletion (only for own messages)
	const deleteBtn = (data.is_me && data.id) ? `
	    <button class="pwo-delete-btn absolute -left-8 top-2 text-gray-400 hover:text-red-500 shadow-sm" data-id="${data.id}" title="Delete Message">
	        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
	            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
	        </svg>
	    </button>
	` : '';
	
    // 6. Assemble HTML
    div.innerHTML = `
        <div class="relative group max-w-[85%] ${data.is_me ? 'msg-me' : ''}">
            ${deleteBtn}
            <div class="msg-body ${data.is_me ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-none'} p-3 shadow-sm text-sm">
                ${contentHTML}
            </div>
            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${statusColor}">
                <span>${data.d_created ? data.d_created.split(' ')[1].substring(0,5) : new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', hour12:false})}</span>
                ${data.is_me ? '<span class="msg-status font-bold">' + statusIcon + '</span>' : ''}
            </div>
        </div>
    `;

    // 7. Append and Scroll
    chatBox.appendChild(div);
    if (isNew) chatBox.scrollTop = chatBox.scrollHeight;
}
