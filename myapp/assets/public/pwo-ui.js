/**
 * pwo-ui.js
 * Handles all visual rendering and text formatting
 */

// 1. Simple Markdown & Formatting Parser
export function parseMarkdown(t) {
    if (!t) return '';
    return t
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') // Sanitize
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')                 // Bold
        .replace(/\*(.*?)\*/g, '<em>$1</em>')                             // Italic
        .replace(/`([^`]+)`/g, '<code class="bg-gray-100 px-1 rounded">$1</code>') // Inline Code
        .replace(/\n/g, '<br>');                                           // New Lines
}

// 2. Main Render Engine
export function render(data, isNew = false, isPending = false) {
    const chatBox = document.getElementById('chat-box');
    if (!chatBox) return;

    // Remove any existing pending message with the same temp_id
    if (data.temp_id) {
        const existing = document.querySelector(`[data-temp-id="${data.temp_id}"]`);
        if (existing) existing.remove();
    }

    const div = document.createElement('div');
    div.className = `flex ${data.is_me ? 'justify-end' : 'justify-start'} mb-4 opacity-0 transition-opacity duration-300`;
    if (data.temp_id) div.setAttribute('data-temp-id', data.temp_id);

    const isAudio = data.file_name && (data.file_name.endsWith('.webm') || data.file_name.endsWith('.wav') || data.file_name.endsWith('.mp3'));
    const isImage = data.file_name && (data.file_name.endsWith('.jpg') || data.file_name.endsWith('.png') || data.file_name.endsWith('.gif'));

    let contentHTML = '';

    // Handle File Attachments
    if (isAudio) {
        contentHTML = `<audio controls src="${data.file_url || data.message}" class="max-w-full"></audio>`;
    } else if (isImage) {
        contentHTML = `<img src="${data.file_url || data.message}" class="max-w-xs rounded-lg shadow-sm cursor-pointer" onclick="window.open(this.src)" />`;
    } else if (data.file_name) {
        contentHTML = `<a href="${data.file_url || '#'}" target="_blank" class="flex items-center gap-2 text-blue-600 underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
            ${data.file_name}
        </a>`;
    } else {
        // Handle Standard Text
        contentHTML = parseMarkdown(data.message);
    }

    const statusIcon = isPending ? '🕒' : (data.is_read ? '✓✓' : '✓');
    const statusColor = data.is_read ? 'text-sky-400' : 'text-gray-400';

    div.innerHTML = `
        <div class="relative group max-w-[80%] ${data.is_me ? 'msg-me' : ''}">
            <div class="${data.is_me ? 'bg-emerald-600 text-white rounded-br-none' : 'bg-gray-100 text-gray-800 rounded-bl-none'} p-3 rounded-2xl text-sm shadow-sm">
                ${contentHTML}
            </div>
            <div class="flex items-center justify-end gap-1 mt-1 text-[10px] ${statusColor}">
                <span>${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                ${data.is_me ? `<span class="msg-status font-bold">${statusIcon}</span>` : ''}
            </div>
        </div>
    `;

    chatBox.appendChild(div);

    // Trigger animation
    setTimeout(() => div.classList.remove('opacity-0'), 10);

    // Auto-scroll
    if (isNew || chatBox.scrollTop + chatBox.clientHeight > chatBox.scrollHeight - 100) {
        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
    }
}
