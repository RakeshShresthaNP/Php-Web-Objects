export class ChatSearch {
    constructor(chatBoxId, searchNavId, countId) {
        this.chatBox = document.getElementById(chatBoxId);
        this.searchNav = document.getElementById(searchNavId);
        this.countDisplay = document.getElementById(countId);
        this.matches = [];
        this.currentIndex = -1;
        this.query = "";
    }

	perform(query) {
	    this.query = query.trim().toLowerCase();
	    const paragraphs = this.chatBox.querySelectorAll('.message-text');
	    this.matches = [];

	    if (this.query.length < 2) {
	        this.reset();
	        this.updateUI();
	        return;
	    }

	    paragraphs.forEach(p => {
	        const originalText = p.textContent; 

	        if (originalText.toLowerCase().includes(this.query)) {
	            const safeQuery = this.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
	            const regex = new RegExp(`(${safeQuery})`, 'gi');
	            
	            p.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-300 rounded-sm search-mark">$1</mark>');
	            this.matches.push(p);

	            let bubble = p.closest('.mb-4'); 
	            if (bubble) bubble.style.display = 'flex'; 
	        } else {
	            p.textContent = originalText;
	        }
	    });

	    this.currentIndex = this.matches.length > 0 ? 0 : -1;
	    this.updateUI();
	    if (this.currentIndex !== -1) this.jumpToMatch();
	}
				
    navigate(direction) {
        if (this.matches.length === 0) return;

        this.currentIndex += direction;
        if (this.currentIndex >= this.matches.length) this.currentIndex = 0;
        if (this.currentIndex < 0) this.currentIndex = this.matches.length - 1;

        this.jumpToMatch();
        this.updateUI();
    }

    jumpToMatch() {
        // Remove focus from all marks
        this.chatBox.querySelectorAll('mark').forEach(m => m.classList.replace('bg-orange-400', 'bg-yellow-300'));

        const target = this.matches[this.currentIndex];
        const mark = target.querySelector('mark');
        
        // Highlight active mark
        if (mark) mark.classList.replace('bg-yellow-300', 'bg-orange-400');

        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    reset() {
        this.matches = [];
        this.currentIndex = -1;

        this.chatBox.querySelectorAll('.message-text').forEach(p => {
            p.innerHTML = p.textContent;
        });
    }

	updateUI() {
	    if (this.query.length >= 2 && this.matches.length > 0) {
	        this.searchNav.classList.remove('hidden');
	        this.searchNav.classList.add('flex');
	        
	        // Consistent colorful classes
	        const btnClass = "bg-slate-800 hover:bg-slate-700 text-white px-3 py-1 rounded-md text-[12px] font-bold shadow-sm transition-colors";
	        const clearBtnClass = "bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-[12px] font-bold shadow-sm transition-colors";
	        const countClass = "bg-emerald-600 text-white px-3 py-1 rounded-md text-[12px] font-bold shadow-sm";
	        
	        this.searchNav.innerHTML = `
	            <button id="pwo-search-prev" class="${btnClass}">↑ Prev</button>
	            <span class="${countClass}">
	                ${this.currentIndex + 1} / ${this.matches.length}
	            </span>
	            <button id="pwo-search-next" class="${btnClass}">↓ Next</button>
	            <button id="pwo-search-clear" class="${clearBtnClass}">Clear ✕</button>
	        `;

	        // Attach Listeners
	        document.getElementById('pwo-search-prev').onclick = () => this.navigate(-1);
	        document.getElementById('pwo-search-next').onclick = () => this.navigate(1);
	        document.getElementById('pwo-search-clear').onclick = () => {
	            const searchInput = document.getElementById('pwo-search-input');
	            if (searchInput) searchInput.value = ""; // Reset input field
	            this.reset(); // Clean marks and highlights
	            this.updateUI(); // Hide the nav
	        };
	    } else {
	        this.searchNav.classList.add('hidden');
	        this.searchNav.classList.remove('flex');
	    }
	}
}

