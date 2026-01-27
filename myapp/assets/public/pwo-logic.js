import { render } from './pwo-ui.js';

// Global variables to maintain state across recording sessions
let animationId;
let audioCtx;
let analyser;
let dataArray;
let timerInterval;
let seconds = 0;
let finalTranscriptStored = ""; // Persistent memory for speech-to-text

// Helper for auth headers
const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

// 1. Initialize Speech Recognition
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = SpeechRecognition ? new SpeechRecognition() : null;
if (recognition) {
    recognition.continuous = true;
    recognition.interimResults = true;
}

// --- HANDLE FILE SELECTION ---
export function handleFile(f, state) {
    if (!f) return;
    if (f.size > 10 * 1024 * 1024) { alert("File too large (max 10MB)"); return; }
    
    state.isReadingFile = true;
    const preview = document.getElementById('pwo-preview');
    const filename = document.getElementById('pwo-filename');
    
    preview.classList.remove('hidden');
    filename.innerText = "Preparing file...";

    const r = new FileReader();
    r.onload = (ev) => { 
        state.pendingFile = { 
            data: ev.target.result, 
            name: f.name,
            localUrl: URL.createObjectURL(f) 
        }; 
        filename.innerText = "✓ " + f.name; 
        state.isReadingFile = false; 
    };
    r.readAsDataURL(f);
}

// --- HANDLE MESSAGE SENDING ---
export async function handleSend(state, ws) {
    if (state.isRecording) stopRecording(state);
    
    // Wait if file is still being processed by FileReader
    let waitCount = 0;
    while (state.isReadingFile && waitCount < 20) { 
        await new Promise(r => setTimeout(r, 100)); 
        waitCount++; 
    }
    
    const chatIn = document.getElementById('chat-in');
    const txt = chatIn.value.trim();
    const t = localStorage.getItem('pwoToken');
    if (!txt && !state.pendingFile) return;
    
    const tempId = Date.now();
    const prog = document.getElementById('pwo-progress-container');
    const bar = document.getElementById('pwo-progress-bar');
    
    if (state.pendingFile) {
        if(prog) prog.classList.remove('hidden');
        const data = state.pendingFile.data, CHUNK = 16384, total = Math.ceil(data.length / CHUNK);
        
        for (let i = 0; i < total; i++) {
            ws.call('chat', 'uploadchunk', { 
                file_id: tempId, 
                chunk: data.substring(i * CHUNK, (i + 1) * CHUNK), 
                index: i, 
                token: t 
            }, getAuthHeaders());
            
            if(bar) bar.style.width = ((i + 1) / total) * 100 + '%';
            await new Promise(r => setTimeout(r, 35)); // Safety delay for server writes
        }
        
        await new Promise(r => setTimeout(r, 100));
        ws.call('chat', 'send', { message: txt, file_id: tempId, file_name: state.pendingFile.name, token: t }, getAuthHeaders());
    } else {
        ws.call('chat', 'send', { message: txt, token: t }, getAuthHeaders());
    }
    
    // --- RESET EVERYTHING AFTER SEND ---
    chatIn.value = ''; 
    finalTranscriptStored = ""; // CRITICAL: Reset transcription memory
    state.pendingFile = null;
    document.getElementById('pwo-preview').classList.add('hidden');
    setTimeout(() => { if(prog) prog.classList.add('hidden'); if(bar) bar.style.width = '0%'; }, 500);
}

// --- HANDLE MIC (Recording + Transcribe + Visualizer) ---
export async function handleMic(state) {
    const chatIn = document.getElementById('chat-in');
    const canvas = document.getElementById('pwo-waveform');
    const timerDisplay = document.getElementById('pwo-timer');
    const ctx = canvas.getContext('2d');
    
    if (!state.isRecording) {
        // Step 1: Initialize Persistent Memory
        finalTranscriptStored = chatIn.value ? chatIn.value.trim() + " " : "";

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            // Audio Visualizer Setup
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            const source = audioCtx.createMediaStreamSource(stream);
            source.connect(analyser);
            analyser.fftSize = 256;
            dataArray = new Uint8Array(analyser.frequencyBinCount);

            // Media Recorder Setup (Voice File)
            state.mediaRecorder = new MediaRecorder(stream);
            state.audioChunks = [];
            
            state.mediaRecorder.ondataavailable = e => { if (e.data.size > 0) state.audioChunks.push(e.data); };
            
            state.mediaRecorder.onstop = () => {
                const blob = new Blob(state.audioChunks, { type: 'audio/webm' });
                const r = new FileReader();
                r.onload = (ev) => {
                    state.pendingFile = { 
                        data: ev.target.result, 
                        name: `voice_${Date.now()}.webm`,
                        localUrl: URL.createObjectURL(blob)
                    };
                    document.getElementById('pwo-filename').innerText = "✓ Voice Note Ready";
                    document.getElementById('pwo-preview').classList.remove('hidden');
                    stream.getTracks().forEach(t => t.stop());
                };
                r.readAsDataURL(blob);
            };

            // Transcription Logic (Append Fix)
            if (recognition) {
                recognition.onresult = (e) => {
                    let interim = '', sessionFinal = '';
                    for (let i = e.resultIndex; i < e.results.length; ++i) {
                        if (e.results[i].isFinal) sessionFinal += e.results[i][0].transcript;
                        else interim += e.results[i][0].transcript;
                    }
                    if (sessionFinal !== "") finalTranscriptStored += sessionFinal + " ";
                    chatIn.value = finalTranscriptStored + interim;
                    chatIn.scrollTop = chatIn.scrollHeight;
                };
                recognition.start();
            }

            // UI: Start Timer
            seconds = 0;
            timerInterval = setInterval(() => {
                seconds++;
                const mins = Math.floor(seconds / 60), secs = seconds % 60;
                timerDisplay.innerText = `● ${mins}:${secs.toString().padStart(2, '0')}`;
            }, 1000);

            // UI: Start Visualizer
            const draw = () => {
                animationId = requestAnimationFrame(draw);
                analyser.getByteFrequencyData(dataArray);
                ctx.fillStyle = '#f0fdf4'; 
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                const barWidth = (canvas.width / dataArray.length) * 2.5;
                let x = 0;
                for (let i = 0; i < dataArray.length; i++) {
                    const barHeight = dataArray[i] / 2;
                    ctx.fillStyle = '#10b981';
                    ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                    x += barWidth + 1;
                }
            };
            draw();

            state.mediaRecorder.start();
            state.isRecording = true;
            document.getElementById('pwo-mic').classList.add('rec-active');
            document.getElementById('pwo-rec-panel').classList.remove('hidden');
            
        } catch (e) { console.error(e); alert("Mic access denied."); }
    } else {
        stopRecording(state);
    }
}

function stopRecording(state) {
    if (recognition) recognition.stop();
    if (state.mediaRecorder && state.mediaRecorder.state !== 'inactive') {
        state.mediaRecorder.stop();
    }
    if (animationId) cancelAnimationFrame(animationId);
    if (audioCtx) audioCtx.close();
    clearInterval(timerInterval);
    
    state.isRecording = false;
    document.getElementById('pwo-mic').classList.remove('rec-active');
    document.getElementById('pwo-rec-panel').classList.add('hidden');
}

// --- Handle Message Deletion (Route #31) ---
export function initDeleteHandler(ws) {
    const chatBox = document.getElementById('chat-box');
    
    // Remove any old listener and add a fresh one to the PARENT
    chatBox.onclick = (e) => {
        const btn = e.target.closest('.pwo-delete-btn');
        if (!btn) return;

        const msgId = btn.getAttribute('data-id');
        if (!msgId) {
            console.error("No ID found on delete button");
            return;
        }

        if (confirm("Delete this message?")) {
            // Call the WS route
            ws.call('chat', 'delete', { 
                id: msgId, 
                token: localStorage.getItem('pwoToken') 
            }, { 'X-Forwarded-Host': window.location.hostname });

            // Remove it from UI immediately
            btn.closest('.flex.mb-4').remove();
        }
    };
}

// --- Handle Local Search (Local Filter) ---
export function initSearchHandler() {
    const toggle = document.getElementById('pwo-search-toggle');
    const input = document.getElementById('pwo-search-input');
    const chatBox = document.getElementById('chat-box');
    
    if (!toggle || !input || !chatBox) return;

    toggle.onclick = () => {
        input.classList.toggle('hidden');
        if (!input.classList.contains('hidden')) {
            input.focus();
        } else {
            input.value = '';
            filterMessages('');
        }
    };

    input.oninput = (e) => {
        const term = e.target.value.toLowerCase().trim();
        filterMessages(term);
    };

	function filterMessages(term) {
	    const chatBox = document.getElementById('chat-box');
	    // We target EVERY direct child of chat-box (the message rows)
	    const messageRows = chatBox.querySelectorAll(':scope > div');
	    
	    messageRows.forEach(row => {
	        // Find the body inside this row
	        const body = row.querySelector('.msg-body');
	        if (!body) return; // Skip if it's not a message bubble

	        const text = body.textContent.toLowerCase();
	        
	        if (text.includes(term)) {
	            row.style.setProperty('display', 'flex', 'important');
	        } else {
	            row.style.setProperty('display', 'none', 'important');
	        }
	    });
	}
}

// --- Offline Queue Support ---
export function processOfflineQueue(ws) {
    const queue = JSON.parse(localStorage.getItem('pwo_offline_queue') || '[]');
    if (queue.length === 0) return;

    console.log(`Connection restored. Syncing ${queue.length} messages...`);
    
    queue.forEach(msg => {
        ws.call('chat', 'send', { 
            message: msg.message, 
            token: localStorage.getItem('pwoToken') 
        }, getAuthHeaders());
    });

    localStorage.removeItem('pwo_offline_queue');
}

