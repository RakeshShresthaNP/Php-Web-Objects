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
	    
	    // If query is too short, show everything as normal
	    if (this.query.length < 2) {
	        this.reset();
	        this.updateUI();
	        return;
	    }

	    // IMPORTANT: Don't call this.reset() at the start of every keystroke 
	    // if it clears the DOM. Instead, just clean the highlights.
	    const paragraphs = this.chatBox.querySelectorAll('.message-text');
	    this.matches = [];

	    paragraphs.forEach(p => {
	        const text = p.getAttribute('data-original-text') || p.textContent;
	        
	        // Save the original text so we don't lose it during multiple highlights
	        if (!p.hasAttribute('data-original-text')) {
	            p.setAttribute('data-original-text', text);
	        }

	        if (text.toLowerCase().includes(this.query)) {
	            const regex = new RegExp(`(${this.query})`, 'gi');
	            p.innerHTML = text.replace(regex, '<mark class="bg-yellow-300 rounded-sm search-mark">$1</mark>');
	            this.matches.push(p);
	            p.closest('.mb-4').style.display = ''; // Ensure the parent bubble is visible
	        } else {
	            p.innerHTML = text;
	            // OPTIONAL: If you DO want to filter out non-matches, uncomment below:
	            // p.closest('.mb-4').style.display = 'none'; 
	        }
	    });

	    this.currentIndex = this.matches.length > 0 ? 0 : -1;
	    if (this.currentIndex !== -1) this.jumpToMatch();
	    this.updateUI();
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
