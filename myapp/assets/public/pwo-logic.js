import { render } from './pwo-ui.js';

// Internal Variables for Mic & Timer
let animationId;
let audioCtx;
let analyser;
let dataArray;
let timerInterval;
let seconds = 0;
let finalTranscriptStored = "";

// Internal Auth Header Helper
const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

// 1. Initialize Speech Recognition
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = SpeechRecognition ? new SpeechRecognition() : null;
if (recognition) {
    recognition.continuous = true;
    recognition.interimResults = true;
}

// --- EXPORTED: Handle File Selection ---
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
        // EXTENSION: Create localUrl for instant UI rendering
        const localUrl = URL.createObjectURL(f); 
        
        state.pendingFile = { 
            data: ev.target.result, 
            name: f.name,
            localUrl: localUrl // Store for UI
        }; 
        filename.innerText = "✓ " + f.name; 
        state.isReadingFile = false; 
    };
    r.readAsDataURL(f);
}

// --- EXPORTED: Handle Message Sending ---
export async function handleSend(state, ws) {
    if (state.isRecording) stopRecording(state);
    
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
    
    // EXTENSION: Pass localUrl and file_name to render immediately
	/*
    render({ 
        message: txt, 
        is_me: true, 
        temp_id: tempId, 
        file_name: state.pendingFile?.name,
        localUrl: state.pendingFile?.localUrl 
    }, true, true);
	*/
    
    const prog = document.getElementById('pwo-progress-container'), bar = document.getElementById('pwo-progress-bar');
    
    if (state.pendingFile) {
        if(prog) prog.classList.remove('hidden');
        const data = state.pendingFile.data, CHUNK = 16384, total = Math.ceil(data.length / CHUNK);
        
        for (let i = 0; i < total; i++) {
            ws.call('chat', 'uploadchunk', { file_id: tempId, chunk: data.substring(i * CHUNK, (i + 1) * CHUNK), index: i, token: t }, getAuthHeaders());
            if(bar) bar.style.width = ((i + 1) / total) * 100 + '%';
            
            // CORRUPTION FIX: Increased delay to 30ms to ensure server handles the write safely
            await new Promise(r => setTimeout(r, 30)); 
        }
        
        // Ensure final write buffer has cleared before sending 'send' command
        await new Promise(r => setTimeout(r, 100));
        ws.call('chat', 'send', { message: txt, file_id: tempId, file_name: state.pendingFile.name, temp_id: tempId, token: t }, getAuthHeaders());
    } else {
        ws.call('chat', 'send', { message: txt, temp_id: tempId, token: t }, getAuthHeaders());
    }
    
    chatIn.value = ''; state.pendingFile = null;
    document.getElementById('pwo-preview').classList.add('hidden');
    setTimeout(() => { if(prog) prog.classList.add('hidden'); if(bar) bar.style.width = '0%'; }, 500);
}

// --- EXPORTED: Handle Mic (Recording + Waveform + Timer) ---
export async function handleMic(state) {
    const chatIn = document.getElementById('chat-in');
    const canvas = document.getElementById('pwo-waveform');
    const timerDisplay = document.getElementById('pwo-timer');
    const ctx = canvas.getContext('2d');
    
    if (!state.isRecording) {
		finalTranscriptStored = chatIn.value ? chatIn.value.trim() + " " : "";
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            const source = audioCtx.createMediaStreamSource(stream);
            source.connect(analyser);
            analyser.fftSize = 256;
            dataArray = new Uint8Array(analyser.frequencyBinCount);

            state.mediaRecorder = new MediaRecorder(stream);
            state.audioChunks = [];
            
            // Timer Logic
            seconds = 0;
            timerDisplay.innerText = "● 0:00";
            timerInterval = setInterval(() => {
                seconds++;
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                timerDisplay.innerText = `● ${mins}:${secs.toString().padStart(2, '0')}`;
            }, 1000);

            // Waveform Logic
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

            state.mediaRecorder.ondataavailable = e => state.audioChunks.push(e.data);
            state.mediaRecorder.onstop = () => {
                const blob = new Blob(state.audioChunks, { type: 'audio/webm' });
                const r = new FileReader();
                r.onload = (ev) => {
                    state.pendingFile = { data: ev.target.result, name: `voice_${Date.now()}.webm` };
                    document.getElementById('pwo-filename').innerText = "✓ Voice Note Ready";
                    document.getElementById('pwo-preview').classList.remove('hidden');
                    stream.getTracks().forEach(t => t.stop());
                };
                r.readAsDataURL(blob);
            };

			if (recognition) {
	            recognition.continuous = true; // Key for appending
	            recognition.interimResults = true;

	            recognition.onresult = (e) => {
	                let interimTranscript = '';
	                let currentSessionFinal = '';

	                for (let i = e.resultIndex; i < e.results.length; ++i) {
	                    if (e.results[i].isFinal) {
	                        currentSessionFinal += e.results[i][0].transcript;
	                    } else {
	                        interimTranscript += e.results[i][0].transcript;
	                    }
	                }

	                // Update the global store with confirmed text
	                // We use += only for things marked as Final by the engine
	                const updatedText = finalTranscriptStored + currentSessionFinal;
	                
	                // Display: Persistent Base + New Final + Thinking (interim)
	                chatIn.value = updatedText + interimTranscript;
	            };

	            recognition.onend = () => {
	                // When mic stops, save the final state into our global store 
	                // so the next time you click mic, it starts from here.
	                finalTranscriptStored = chatIn.value; 
	                
	                chatIn.focus();
	                const len = chatIn.value.length;
	                chatIn.setSelectionRange(len, len);
	            };

	            recognition.start();
	        }
											
            state.mediaRecorder.start();
            state.isRecording = true;
            document.getElementById('pwo-mic').classList.add('rec-active');
            document.getElementById('pwo-rec-panel').classList.remove('hidden');
            
        } catch (e) { alert("Mic error."); }
    } else {
        stopRecording(state);
    }
}

// Internal Stop Function
function stopRecording(state) {
    if (state.mediaRecorder && state.mediaRecorder.state !== 'inactive') {
        state.mediaRecorder.stop();
        if (recognition) recognition.stop();
        if (animationId) cancelAnimationFrame(animationId);
        if (audioCtx) audioCtx.close();
        clearInterval(timerInterval);
        state.isRecording = false;
        document.getElementById('pwo-mic').classList.remove('rec-active');
        document.getElementById('pwo-rec-panel').classList.add('hidden');
    }
}
