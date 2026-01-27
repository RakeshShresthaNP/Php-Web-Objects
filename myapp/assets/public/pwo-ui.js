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

    // --- 1. SENDER LOGIC ---
    // Extract current user ID from localStorage (set this during login)
    const myId = parseInt(localStorage.getItem('pwoUserId')); 
    // If data.is_me isn't explicitly passed, calculate it from sender_id
    const isMe = data.is_me || (data.sender_id === myId);

    // --- 2. DATA KEYS (From your Console Log) ---
    const msgId = data.id;
    // Format "2026-01-27 04:19:18" -> "04:19"
    const msgTime = data.time ? data.time.split(' ')[1].substring(0, 5) : 
                   new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });

    // --- 3. DELETE BUTTON (Internal Placement) ---
    const deleteBtn = (isMe && msgId) ? `
        <button class="pwo-delete-btn opacity-0 absolute -top-2 -right-2 bg-white rounded-full w-6 h-6 flex items-center justify-center shadow-md text-gray-400 hover:text-red-500 z-50 border border-gray-100 transition-all hover:scale-110" data-id="${msgId}" title="Delete Message">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    ` : '';

    // --- 4. CONTENT LOGIC (Files/Voice/Text) ---
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

    // --- 5. ASSEMBLE ROW ---
    const div = document.createElement('div');
    div.className = `flex mb-4 ${isMe ? 'justify-end' : 'justify-start'}`;
    if (isTemp) div.id = `temp-${data.temp_id}`;

    div.innerHTML = `
        <div class="relative group max-w-[85%] ${isMe ? 'msg-me' : ''}" style="overflow: visible;">
            ${deleteBtn}
            <div class="msg-body ${isMe ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-none'} p-3 shadow-sm text-sm">
                ${contentHTML}
            </div>
            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${data.is_read ? 'text-sky-400' : 'text-gray-400'}">
                <span>${msgTime}</span>
                ${isMe ? '<span class="msg-status font-bold">' + (data.is_read ? '✓✓' : '✓') + '</span>' : ''}
            </div>
        </div>
    `;

    // --- 6. APPEND & SCROLL ---
    chatBox.appendChild(div);
    if (isNew) chatBox.scrollTop = chatBox.scrollHeight;
}
