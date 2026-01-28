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

window.addEventListener('pwo_open_pdf', (e) => {
    const fileUrl = e.detail;
    const modal = document.getElementById('pdf-modal');
    const frame = document.getElementById('pdf-frame');
    
    if (modal && frame) {
        frame.src = fileUrl; // Load the PDF into the iframe
        modal.classList.remove('hidden'); // Show the modal
    }
});

window.addEventListener('pwo_open_image', (e) => {
    const fileUrl = e.detail;
    const lightbox = document.getElementById('pwo-lightbox');
    const img = document.getElementById('pwo-lightbox-img');
    const dl = document.getElementById('pwo-lightbox-download');
    
    if (lightbox && img) {
        img.src = fileUrl;
        if (dl) dl.href = fileUrl; // Let users download from the zoom view
        lightbox.classList.remove('hidden');
    }
});

// Close Image Lightbox Logic
window.addEventListener('pwo_open_video', (e) => {
    // 1. Pause every video in the chat bubbles
    document.querySelectorAll('video').forEach(v => {
        v.pause();
        v.muted = true;
    });

    const fileUrl = e.detail;
    const modal = document.getElementById('video-modal');
    const player = document.getElementById('modal-video-player');
    
    if (modal && player) {
        player.src = fileUrl;
        modal.classList.remove('hidden');
        player.load();
        player.play();
    }
});

window.closeVideoModal = function() {
    const modal = document.getElementById('video-modal');
    const player = document.getElementById('modal-video-player');
    
    if (player) {
        player.pause();
        player.src = ""; 
        player.load(); // Forces the browser to release the video file
    }
    
    if (modal) {
        modal.classList.add('hidden');
    }
};

window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        // 1. Close Video Modal
        if (typeof window.closeVideoModal === 'function') {
            window.closeVideoModal();
        }

        // 2. Close PDF Modal
        const pdfModal = document.getElementById('pdf-modal');
        if (pdfModal && !pdfModal.classList.contains('hidden')) {
            pdfModal.classList.add('hidden');
            const pdfFrame = document.getElementById('pdf-frame');
            if (pdfFrame) pdfFrame.src = ""; // Clear memory
        }

        // 3. Close Image Lightbox
        const lightbox = document.getElementById('pwo-lightbox');
        if (lightbox && !lightbox.classList.contains('hidden')) {
            lightbox.classList.add('hidden');
        }
    }
});

window.addEventListener('mousedown', (e) => {
    const videoModal = document.getElementById('video-modal');
    const pdfModal = document.getElementById('pdf-modal');
    const lightbox = document.getElementById('pwo-lightbox');

    if (e.target === videoModal) {
        window.closeVideoModal(); // Calls the function that pauses video
    }
    if (e.target === pdfModal) pdfModal.classList.add('hidden');
    if (e.target === lightbox) lightbox.classList.add('hidden');
});

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
		    const fileName = data.file_name || '';
		    const isVoice = fileName.endsWith('.webm') || fileName.endsWith('.wav');
		    const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName);
		    const isVideo = /\.(mp4|webm|ogg)$/i.test(fileName);
		    const isPDF = /\.pdf$/i.test(fileName); // NEW: PDF Check

		    if (isVoice) {
		        contentHTML = `<div class="w-[220px] mb-1"><audio controls class="w-full h-8"><source src="${fileUrl}" type="audio/webm"></audio></div>`;
		    } else if (isImage) {
				contentHTML = `
				        <div class="mb-2 group relative cursor-zoom-in overflow-hidden rounded-lg border border-white/10 shadow-sm"
				             onclick="window.dispatchEvent(new CustomEvent('pwo_open_image', {detail: '${fileUrl}'}))">
				            
				            <img src="${fileUrl}" 
				                 class="w-full h-auto transition-transform duration-500 group-hover:scale-110 group-hover:brightness-110" />
				            
				            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
				                 <span class="bg-white/20 backdrop-blur-md text-white text-[10px] font-bold py-1 px-3 rounded-full border border-white/20">
				                    VIEW PHOTO ⤢
				                 </span>
				            </div>
				        </div>`;
			} else if (isVideo) {
				const sanitizedUrl = fileUrl.replace(/\\/g, '/');
			    const timestampId = `vid-time-${Math.random().toString(36).substr(2, 5)}`;
			    const videoId = `vid-obj-${Math.random().toString(36).substr(2, 5)}`;
			    const progressId = `prog-${videoId}`;

			    contentHTML = `
			        <div class="mb-2 w-full max-w-[280px] group relative overflow-hidden rounded-lg shadow-sm border border-white/10 bg-black"
			             onmouseenter="const v=document.getElementById('${videoId}'); v.muted=true; v.play(); this.classList.add('playing')"
			             onmouseleave="const v=document.getElementById('${videoId}'); v.pause(); v.currentTime=0; this.classList.remove('playing')">
			            
			            <video id="${videoId}" preload="metadata" class="w-full h-auto block cursor-pointer"
			                   onloadedmetadata="
			                       const min = Math.floor(this.duration / 60);
			                       const sec = Math.floor(this.duration % 60);
			                       const el = document.getElementById('${timestampId}');
			                       if(el) { el.innerText = min + ':' + (sec < 10 ? '0' : '') + sec; el.classList.remove('hidden'); }
			                   "
			                   ontimeupdate="
			                       const p = (this.currentTime / this.duration) * 100;
			                       const bar = document.getElementById('${progressId}');
			                       if(bar) bar.style.width = p + '%';
			                   "
			                   onclick="this.muted=false; this.paused ? this.play() : this.pause()">
			                <source src="${sanitizedUrl}" type="video/mp4">
			            </video>

			            <div class="absolute inset-0 flex items-center justify-center pointer-events-none transition-opacity group-hover:opacity-0">
			                <div class="pwo-video-overlay-circle">
			                    <div class="pwo-play-triangle"></div>
			                </div>
			            </div>

			            <div id="${timestampId}" class="pwo-video-duration hidden">0:00</div>
			            
			            <div id="${progressId}" class="pwo-video-progress" style="width: 0%"></div>
			            
			            <div class="absolute top-2 right-2 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
			                <button onclick="window.dispatchEvent(new CustomEvent('pwo_open_video', {detail: '${sanitizedUrl}'}))" 
			                        class="p-1.5 bg-black/60 backdrop-blur-md rounded-lg text-white hover:bg-emerald-600 transition-colors">
			                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5v-4m0 4h-4m4 0l-5-5"></path></svg>
			                </button>
			                
			                <a href="${sanitizedUrl}" download="${fileName}" onclick="event.stopPropagation()"
			                   class="p-1.5 bg-black/60 backdrop-blur-md rounded-lg text-white hover:bg-emerald-600 transition-colors">
			                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
			                </a>
			            </div>
			        </div>`;
			} else if (isPDF) {
				contentHTML = `
				        <div class="flex flex-col gap-2 p-3 bg-red-50 border border-red-100 rounded-xl mb-2 cursor-pointer hover:bg-red-100 transition-all group"
				             onclick="window.dispatchEvent(new CustomEvent('pwo_open_pdf', {detail: '${fileUrl}'}))">
				            <div class="flex items-center justify-between">
				                <div class="flex items-center gap-3">
				                    <div class="bg-red-500 text-white p-2 rounded-lg">
				                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"></path></svg>
				                    </div>
				                    <div class="flex flex-col overflow-hidden">
				                        <span class="text-xs font-bold truncate w-[140px] text-gray-800">${fileName}</span>
				                        <span class="text-[10px] text-red-600 font-semibold uppercase tracking-tight">Open in Viewer</span>
				                    </div>
				                </div>
				                <a href="${fileUrl}" download="${fileName}" onclick="event.stopPropagation()" class="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-200 rounded transition-all">
				                     <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
				                </a>
				            </div>
				            <div class="mt-1 h-24 w-full overflow-hidden rounded-lg pointer-events-none border border-red-200/50">
				                <embed src="${fileUrl}#toolbar=0&navpanes=0&scrollbar=0" type="application/pdf" width="100%" height="100%" />
				            </div>
				        </div>`;
			} else {
		        // CATCH-ALL for other files (zip, docx, etc.)
		        contentHTML = `<a href="${fileUrl}" target="_blank" class="flex items-center gap-2 p-2 bg-black/5 rounded-lg mb-2 text-blue-600 underline text-xs">
		            <span class="truncate max-w-[150px]">${fileName || 'View Attachment'}</span>
		        </a>`;
		    }
		}
				
		if (data.message) {
		    tempP.textContent = data.message; 
		    const safeEscaped = tempP.innerHTML; 
		    const formattedMessage = parseMarkdown(safeEscaped);

		    contentHTML += `
		        <div class="relative group/text">
		            <p class="message-text leading-relaxed whitespace-pre-wrap text-sm">${formattedMessage}</p>
		            
		            <button onclick="navigator.clipboard.writeText('${data.message.replace(/'/g, "\\'")}'); this.innerText='Copied!'; setTimeout(()=>this.innerText='Copy', 2000)"
		                    class="absolute -right-2 -top-2 opacity-0 group-hover/text:opacity-100 transition-opacity bg-white/10 hover:bg-white/20 text-[9px] px-2 py-0.5 rounded border border-white/10 text-white cursor-pointer">
		                Copy
		            </button>
		        </div>`;
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
			    <div class="pwo-msg-container" style="width: fit-content; max-width: 85%; min-width: 100px;">
			        ${deleteBtn}
			        <div class="${isMe ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 border border-gray-200 rounded-2xl rounded-tl-none'} p-3 shadow-sm">
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
				    
	if (isNew) {
	    const threshold = 150; // pixels from bottom
	    const isAtBottom = (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) < threshold;
	    const scrollBadge = document.getElementById('pwo-scroll-bottom');

	    if (isMe || isAtBottom) {
	        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
	        if (scrollBadge) scrollBadge.classList.add('hidden');
	    } else {
	        // Show badge if user is scrolled up and a new message arrives
	        if (scrollBadge) {
	            scrollBadge.classList.remove('hidden');
	            scrollBadge.onclick = () => {
	                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
	                scrollBadge.classList.add('hidden');
	            };
	        }
	    }
	}
}

(function initGlobalScroll() {
    const chatBox = document.getElementById('chat-box');
    const scrollBadge = document.getElementById('pwo-scroll-bottom');

    if (!chatBox || !scrollBadge) return;

    // Handle clicking the badge to scroll to bottom
    scrollBadge.addEventListener('click', () => {
        chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
        scrollBadge.classList.add('hidden');
    });

    // Handle hiding the badge when the user scrolls down manually
    chatBox.addEventListener('scroll', () => {
        const threshold = 100; 
        const isAtBottom = (chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight) < threshold;
        
        if (isAtBottom) {
            scrollBadge.classList.add('hidden');
        }
    });
})();
