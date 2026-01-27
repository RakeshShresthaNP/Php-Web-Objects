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

	            // FIX: Ensure the message bubble is not hidden by any parent styles
	            let bubble = p.closest('.mb-4'); 
	            if (bubble) bubble.style.display = 'flex'; 
	        } else {
	            p.textContent = originalText;
	            // OPTIONAL: If you want to hide non-matching messages, uncomment below:
	            // let bubble = p.closest('.mb-4');
	            // if (bubble) bubble.style.display = 'none';
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
        // Clean all existing highlights
        this.chatBox.querySelectorAll('.message-text').forEach(p => {
            p.innerHTML = p.textContent;
        });
    }

    updateUI() {
        if (this.query.length >= 2 && this.matches.length > 0) {
            this.searchNav.classList.remove('hidden');
            this.searchNav.classList.add('flex');
            this.countDisplay.innerText = `${this.currentIndex + 1}/${this.matches.length}`;
        } else {
            this.searchNav.classList.add('hidden');
            this.searchNav.classList.remove('flex');
        }
    }
}
