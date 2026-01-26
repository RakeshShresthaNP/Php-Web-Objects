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

/**
 * pwo-ui.js
 * Fixed to support the "file_path" field from your database
 */

export function render(data, isNew = false, isPending = false) {
    const chatBox = document.getElementById('chat-box');
    if (!chatBox) return;

    if (data.temp_id) {
        const existing = document.querySelector(`[data-temp-id="${data.temp_id}"]`);
        if (existing) existing.remove();
    }

    const div = document.createElement('div');
    div.className = `flex ${data.is_me ? 'justify-end' : 'justify-start'} mb-4 opacity-0 transition-opacity duration-300`;
    if (data.temp_id) div.setAttribute('data-temp-id', data.temp_id);

    // --- FILE DETECTION LOGIC ---
    const fileName = (data.file_name || "").toLowerCase();
    const isImage = fileName.endsWith('.jpg') || fileName.endsWith('.png') || fileName.endsWith('.jpeg') || fileName.endsWith('.webp') || fileName.endsWith('.gif');
    const isPDF = fileName.endsWith('.pdf');
    const isAudio = fileName.endsWith('.webm') || fileName.endsWith('.wav') || fileName.endsWith('.mp3');

    // CRITICAL: Path Mapping
    // 1. data.localUrl = Used for instant preview while uploading
    // 2. data.file_path = Used for messages loaded from your DB (e.g. public/assets/uploads/...)
    const fileSource = data.localUrl || data.file_path || data.file_url;

    let contentHTML = '';

    if (isImage && fileSource) {
        contentHTML = `<img src="${fileSource}" class="max-w-xs rounded-lg shadow-md cursor-pointer hover:opacity-90 transition" onclick="window.open(this.src)" onerror="this.style.display='none'"/>`;
        if (data.message) contentHTML += `<div class="mt-2">${parseMarkdown(data.message)}</div>`;
    } 
    else if (isPDF && fileSource) {
        contentHTML = `
            <a href="${fileSource}" target="_blank" class="flex items-center gap-3 p-3 bg-white/10 border border-white/20 rounded-xl hover:bg-white/20 transition-all">
                <div class="bg-red-500 p-2 rounded-lg shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <span class="text-xs font-bold truncate max-w-[140px]">${data.file_name}</span>
                    <span class="text-[10px] opacity-60 font-bold uppercase tracking-wider">PDF Document</span>
                </div>
            </a>`;
    }
    else if (isAudio && fileSource) {
        contentHTML = `<audio controls src="${fileSource}" class="max-w-full h-10"></audio>`;
    }
    else if (data.file_name) {
        // Fallback for other file types
        contentHTML = `<a href="${fileSource}" target="_blank" class="text-blue-400 underline text-xs flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
            ${data.file_name}
        </a>`;
    }
    else {
        contentHTML = parseMarkdown(data.message);
    }

    const statusIcon = isPending ? '🕒' : (data.is_read ? '✓✓' : '✓');
    const statusColor = data.is_read ? 'text-sky-400' : 'text-gray-400';

	div.innerHTML = `
	    <div class="relative group max-w-[85%] ${data.is_me ? 'msg-me' : ''}">
	        <div class="msg-scroll-wrapper ${data.is_me ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-gray-100 text-gray-800 rounded-2xl rounded-tl-none'} p-3 shadow-sm text-sm">
	            <div class="msg-inner-content">
	                ${contentHTML}
	            </div>
	        </div>
	        
	        <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${statusColor}">
	            <span>${data.time || new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
	            ${data.is_me ? `<span class="msg-status font-bold">${statusIcon}</span>` : ''}
	        </div>
	    </div>
	`;
	
    chatBox.appendChild(div);
    setTimeout(() => div.classList.remove('opacity-0'), 10);
    if (isNew) chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
}
