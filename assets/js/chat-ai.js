/**
 * assets/js/chat-ai.js
 *
 * Mengelola state chat (LocalStorage), interaksi UI (WhatsApp-like bubble chat, modal picker),
 * animasi mengetik (typing simulator), request ke backend, dan rendering report card final.
 */

document.addEventListener("DOMContentLoaded", () => {
    // ─── STATE VARIABLES ───
    let sessions = [];
    let activeSessionId = null;
    let isFetching = false;
    let typingTimer = null;

    // DOM Elements
    const chatFeed = document.getElementById("chatFeed");
    const chatInput = document.getElementById("chatInput");
    const sendBtn = document.getElementById("sendBtn");
    const voteBtn = document.getElementById("voteBtn");
    const historyScroll = document.getElementById("historyScroll");
    const newChatBtn = document.getElementById("newChatBtn");
    const resetBtn = document.getElementById("resetBtn");
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const chatHeaderSub = document.getElementById("chatHeaderSub");

    // Modal Elements
    const pickerModal = document.getElementById("pickerModal");
    const closeModalBtn = document.getElementById("closeModalBtn");
    const modalSearchInput = document.getElementById("modalSearchInput");
    const modalList = document.getElementById("modalList");
    const externalChatBtn = document.getElementById("externalChatBtn");

    // ─── AI AVATARS CONFIGURATION ───
    const AI_AVATARS = {
        AMBIS: "../assets/images/avatar-supri.png",
        STRATEGIS: "../assets/images/avatar-alita.png",
        REALISTIS: "../assets/images/avatar-budi.png",
        USER: "../assets/images/user_6a117179967cb.jpg" // Fallback user avatar, or custom if session has it
    };

    const DEFAULT_USER_AVATAR = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%237c6af5'><circle cx='12' cy='8' r='4'/><path d='M12 14c-6.1 0-8 4-8 4v2h16v-2s-1.9-4-8-4z'/></svg>";

    // Fallback if images missing
    const getAvatarSrc = (sender) => {
        const senderUpper = sender.toUpperCase();
        if (senderUpper === 'USER') return DEFAULT_USER_AVATAR;
        return AI_AVATARS[senderUpper] || DEFAULT_USER_AVATAR;
    };

    // ─── INITIALIZATION ───
    initHistory();
    setupEventListeners();

    // Automatically check if a competition ID was passed in the URL to start a chat session
    const urlParams = new URLSearchParams(window.location.search);
    const urlCompId = urlParams.get('competition_id');
    if (urlCompId && window.dbCompetitions) {
        const comp = window.dbCompetitions.find(c => String(c.id) === String(urlCompId));
        if (comp) {
            selectCompetitionForChat(comp);
            // Clean URL parameters
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // ─── FUNCTIONS ───

    // Memuat Riwayat Diskusi dari LocalStorage
    function initHistory() {
        const stored = localStorage.getItem('pasti_info_chat_history');
        if (stored) {
            try {
                sessions = JSON.parse(stored);
            } catch (e) {
                console.error("Failed to parse chat history:", e);
                sessions = [];
            }
        }

        // Jika tidak ada riwayat, buat diskusi kasual default
        if (sessions.length === 0) {
            const defaultSession = createNewSessionObject(null);
            sessions = [defaultSession];
            saveSessions();
        }

        // Tentukan sesi aktif pertama
        activeSessionId = sessions[0].id;
        renderHistoryList();
        renderActiveSession();
    }

    // Helper untuk membuat Sesi Diskusi Baru
    function createNewSessionObject(comp) {
        const id = Date.now().toString() + Math.random().toString(36).substring(2, 11);
        const initialMsg = comp
            ? {
                sender: 'STRATEGIS',
                text: `Halo! Kita lihat kamu tertarik sama <strong>${comp.title}</strong>. Yuk kita bedah kesiapanmu dari berbagai sudut pandang! 🎯`,
                time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
              }
            : {
                sender: 'AMBIS',
                text: `Halo! Ada info lomba seru dari luar ya? Coba ceritain di sini dong nama lomba, biaya, dan hadiahnya, biar kita berdebat kelayakanmu!`,
                time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
              };

        return {
            id,
            competition: comp,
            messages: [initialMsg],
            isVotingDone: false,
            createdAt: new Date().toISOString(),
        };
    }

    function saveSessions() {
        localStorage.setItem('pasti_info_chat_history', JSON.stringify(sessions));
    }

    function getActiveSession() {
        return sessions.find(s => s.id === activeSessionId) || null;
    }

    // Render Riwayat Diskusi di Sidebar
    function renderHistoryList() {
        historyScroll.innerHTML = "";
        
        sessions.forEach(s => {
            const isActive = s.id === activeSessionId;
            const emoji = getHistoryIcon(s.competition);
            const title = s.competition ? s.competition.title : 'Kasual / Lomba Luar';
            const meta = getHistoryMeta(s);

            const item = document.createElement("div");
            item.className = `history-item ${isActive ? 'active' : ''}`;
            item.setAttribute("data-id", s.id);

            item.innerHTML = `
                <div class="history-icon">${emoji}</div>
                <div class="history-info">
                    <div class="history-title">${title}</div>
                    <div class="history-meta">${meta}</div>
                </div>
                <div class="history-dot"></div>
                ${sessions.length > 1 ? `
                    <button class="history-delete-btn" title="Hapus riwayat diskusi">
                        <span class="mat">delete</span>
                    </button>
                ` : ''}
            `;

            // Click Handler untuk memuat obrolan
            item.addEventListener("click", () => {
                if (isFetching) return;
                activeSessionId = s.id;
                renderHistoryList();
                renderActiveSession();
                closeSidebarMobile();
            });

            // Click Handler untuk hapus obrolan
            const deleteBtn = item.querySelector(".history-delete-btn");
            if (deleteBtn) {
                deleteBtn.addEventListener("click", (e) => {
                    e.stopPropagation();
                    if (isFetching) return;
                    deleteSession(s.id);
                });
            }

            historyScroll.appendChild(item);
        });
    }

    // Render Percakapan dari Sesi Aktif
    function renderActiveSession() {
        const session = getActiveSession();
        if (!session) return;

        // Update Header & Info Lomba di Sidebar
        const compCardContainer = document.getElementById("compCardContainer");
        if (session.competition) {
            chatHeaderSub.innerHTML = `3 konsultan aktif · ${session.competition.title}`;
            
            const fee = parseInt(session.competition.registration_fee || 0);
            const feeText = fee > 0 ? `Rp ${fee.toLocaleString('id-ID')}` : 'Gratis';
            const audience = session.competition.target_audience || 'Mahasiswa S1 / Umum';
            const dates = session.competition.date_range || 'Sesuai info deskripsi';

            compCardContainer.innerHTML = `
                <div class="comp-card">
                  <div class="comp-badge">
                    <span class="mat" style="font-size: 12px;">workspace_premium</span>
                    ${session.competition.category || 'Umum'}
                  </div>
                  <div class="comp-title">${session.competition.title}</div>
                  <div class="comp-meta">
                    <div class="comp-meta-row">
                      <span class="mat">calendar_today</span> 
                      ${dates}
                    </div>
                    <div class="comp-meta-row">
                      <span class="mat">payments</span> 
                      ${feeText}
                    </div>
                    <div class="comp-meta-row">
                      <span class="mat">people</span> 
                      ${audience}
                    </div>
                  </div>
                </div>
            `;
        } else {
            chatHeaderSub.innerHTML = `3 konsultan aktif · Diskusi Kasual`;
            compCardContainer.innerHTML = `
                <div class="comp-card" style="background: linear-gradient(135deg, #152b47 0%, #1c3654 100%)">
                  <div class="comp-badge" style="color: #fed7aa; border-color: rgba(253,186,116,0.3); background-color: rgba(253,186,116,0.15)">
                    <span class="mat" style="font-size: 12px;">smart_toy</span>
                    Umum / Kasual
                  </div>
                  <div class="comp-title">Konsultasi Lomba Eksternal / Kustom</div>
                  <div class="comp-meta">
                    <div class="comp-meta-row">
                      <span class="mat">info</span> Tulis info lomba luar di obrolan
                    </div>
                    <div class="comp-meta-row">
                      <span class="mat">chat</span> 3 AI Konsultan aktif
                    </div>
                  </div>
                </div>
            `;
        }

        // Kosongkan feed chat
        chatFeed.innerHTML = `
            <div class="day-divider">
              <hr />
              <span>Hari ini</span>
              <hr />
            </div>
        `;

        // Render semua pesan
        session.messages.forEach(msg => {
            if (msg.sender === 'FINAL_REPORT_JSON') {
                renderFinalReportCard(msg.text);
            } else {
                appendMessageBubble(msg.sender, msg.text, msg.time);
            }
        });

        // Kontrol tombol vote
        const userMsgCount = session.messages.filter(m => m.sender === 'USER').length;
        if (voteBtn) voteBtn.disabled = (session.isVotingDone || isFetching || userMsgCount < 2);

        refreshVotePromptInFeed();

        // Scroll ke bawah
        scrollChatToBottom();
    }

    // Mengelola tombol vote prompt melayang di feed obrolan
    function refreshVotePromptInFeed() {
        const session = getActiveSession();
        if (!session) return;

        const existingPrompt = document.getElementById("chatVotePrompt");
        const userMsgCount = session.messages.filter(m => m.sender === 'USER').length;
        
        const shouldShow = (userMsgCount >= 2 && !session.isVotingDone && !isFetching);
        
        if (shouldShow) {
            if (!existingPrompt) {
                const promptDiv = document.createElement("div");
                promptDiv.className = "vote-prompt";
                promptDiv.id = "chatVotePrompt";
                promptDiv.innerHTML = `
                    <div class="vote-prompt-icon">
                        <span class="mat">summarize</span>
                    </div>
                    <div class="vote-prompt-text">
                        <div class="vote-prompt-title">Minta Rekomendasi Akhir</div>
                        <div class="vote-prompt-sub">Semua AI Konsultan siap memberikan keputusan kelayakan.</div>
                    </div>
                    <button class="vote-now-btn" id="feedVoteBtn">
                        <span class="mat">check_circle</span>
                        <span>Minta Keputusan</span>
                    </button>
                `;
                chatFeed.appendChild(promptDiv);
                
                const feedVoteBtn = promptDiv.querySelector("#feedVoteBtn");
                feedVoteBtn.addEventListener("click", () => {
                    sendMessageToAI(true);
                });
                
                scrollChatToBottom();
            }
        } else {
            if (existingPrompt) {
                existingPrompt.remove();
            }
        }
    }

    // Menambah gelembung chat ke UI
    function appendMessageBubble(sender, text, time) {
        const isUser = sender === 'USER';
        let senderLabel = isUser ? 'Kamu' : sender.charAt(0) + sender.slice(1).toLowerCase();
        if (sender === 'AMBIS') senderLabel = 'Supri';
        else if (sender === 'REALISTIS') senderLabel = 'Budi';
        else if (sender === 'STRATEGIS') senderLabel = 'Alita';
        
        const group = document.createElement("div");
        group.className = `msg-group ${isUser ? 'user' : ''}`;

        // Get avatar
        let avatarSrc = getAvatarSrc(sender);
        if (isUser && window.currentUserProfilePicture && !window.currentUserProfilePicture.includes("default_user.png")) {
            // Ambil profile picture user jika didefinisikan dari server PHP dan bukan default_user.png
            avatarSrc = window.currentUserProfilePicture;
        }

        group.innerHTML = `
            <div class="msg-avatar ${sender.toLowerCase()}">
                <img src="${avatarSrc}" alt="${senderLabel}">
            </div>
            <div class="msg-body">
                <div class="msg-sender">${senderLabel}</div>
                <div class="msg-bubble ${sender.toLowerCase()}">${text}</div>
                <div class="msg-time">${time || new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</div>
            </div>
        `;

        chatFeed.appendChild(group);
        scrollChatToBottom();
    }

    // Menampilkan typing indicator di chat
    function showTypingIndicator(sender) {
        removeTypingIndicator();

        let senderLabel = sender.charAt(0) + sender.slice(1).toLowerCase();
        if (sender === 'AMBIS') senderLabel = 'Supri';
        else if (sender === 'REALISTIS') senderLabel = 'Budi';
        else if (sender === 'STRATEGIS') senderLabel = 'Alita';
        const avatarSrc = getAvatarSrc(sender);

        const group = document.createElement("div");
        group.className = "msg-group typing-group";
        group.id = "typingIndicator";

        group.innerHTML = `
            <div class="msg-avatar ${sender.toLowerCase()}">
                <img src="${avatarSrc}" alt="${senderLabel}">
            </div>
            <div class="msg-body">
                <div class="msg-sender">${senderLabel} sedang mengetik...</div>
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;

        chatFeed.appendChild(group);
        scrollChatToBottom();
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById("typingIndicator");
        if (indicator) {
            indicator.remove();
        }
    }

    // Render Kartu Laporan Kelayakan Final
    function renderFinalReportCard(jsonText) {
        let report;
        try {
            report = JSON.parse(jsonText);
        } catch (e) {
            console.error("Failed to parse report JSON:", e);
            return;
        }

        const session = getActiveSession();

        const card = document.createElement("div");
        card.className = "report-card";

        const ambisVote = getPersonaVote('AMBIS');
        const realVote = getPersonaVote('REALISTIS');
        const stratVote = getPersonaVote('STRATEGIS');

        const getVotePill = (name, vote) => {
            if (vote === 'IKUT') return `<div class="vote-pill yes"><div class="vote-pill-dot"></div>${name}: Ikut! 💪</div>`;
            if (vote === 'TIDAK IKUT') return `<div class="vote-pill no"><div class="vote-pill-dot"></div>${name}: Bersiap dulu ⚠️</div>`;
            return `<div class="vote-pill neutral"><div class="vote-pill-dot"></div>${name}: Abstain</div>`;
        };

        const regLinkHtml = (session.competition && session.competition.registration_link)
            ? `<a href="${session.competition.registration_link}" target="_blank" rel="noopener noreferrer" class="report-action-btn primary">
                 <span class="mat" style="font-size: 15px;">open_in_new</span>
                 Daftar Lomba
               </a>`
            : '';

        card.innerHTML = `
            <div class="report-card-header">
                <div class="report-card-header-left">
                  <div class="report-icon"><span class="mat" style="font-size: 22px;">summarize</span></div>
                  <div>
                    <div class="report-title">Laporan Konsultasi Final</div>
                    <div class="report-sub">Kesimpulan dari 3 perspektif AI</div>
                  </div>
                </div>
                <div class="report-score-box">
                  <div class="report-score-val">${report.score}%</div>
                  <div class="report-score-lbl">Skor Kelayakan</div>
                </div>
            </div>
            
            <div class="report-votes">
                ${getVotePill('Supri', ambisVote)}
                ${getVotePill('Alita', stratVote)}
                ${getVotePill('Budi', realVote)}
            </div>

            <div class="report-details-grid">
                <div class="report-column pros">
                  <div class="report-column-header">
                    <span class="mat" style="font-size: 14px;">thumb_up</span>
                    Keuntungan & Peluang
                  </div>
                  <ul class="report-column-list">
                    ${(report.pros || []).map(p => `<li>${p}</li>`).join('')}
                  </ul>
                </div>
                
                <div class="report-column cons">
                  <div class="report-column-header">
                    <span class="mat" style="font-size: 14px;">warning</span>
                    Hambatan & Resiko
                  </div>
                  <ul class="report-column-list">
                    ${(report.cons || []).map(c => `<li>${c}</li>`).join('')}
                  </ul>
                </div>
            </div>

            <div class="report-column next" style="margin-bottom:14px;">
                <div class="report-column-header">
                  <span class="mat" style="font-size: 14px;">assignment_turned_in</span>
                  Rencana Aksi Strategis
                </div>
                <ul class="report-column-list">
                  ${(report.nextSteps || []).map(s => `<li>${s}</li>`).join('')}
                </ul>
            </div>

            <div class="report-actions">
                ${regLinkHtml}
                <button class="report-action-btn secondary share-report-btn">
                  <span class="mat" style="font-size: 15px;">share</span>
                  Bagikan Hasil
                </button>
            </div>
        `;

        // Share event listener
        const shareBtn = card.querySelector(".share-report-btn");
        shareBtn.addEventListener("click", () => {
            const textContent = `Hasil Analisis Kelayakan Lomba Pasti Info:\nSkor Kelayakan: ${report.score}%\n\nKeuntungan:\n${(report.pros || []).map((p,i)=>`${i+1}. ${p}`).join('\n')}\n\nHambatan:\n${(report.cons || []).map((c,i)=>`${i+1}. ${c}`).join('\n')}`;
            if (navigator.share) {
                navigator.share({
                    title: `Laporan Kelayakan AI: ${session.competition ? session.competition.title : 'Lomba Eksternal'}`,
                    text: `Saya mendapat skor kelayakan ${report.score}% untuk ikut lomba!`,
                    url: window.location.href
                }).catch(err => console.log(err));
            } else {
                navigator.clipboard.writeText(textContent).then(() => {
                    alert("Laporan kelayakan telah disalin ke clipboard!");
                });
            }
        });

        chatFeed.appendChild(card);
        scrollChatToBottom();
    }

    function getPersonaVote(persona) {
        const session = getActiveSession();
        if (!session) return null;

        const msg = session.messages.find(m => m.sender === persona && m.text.includes('VOTE:'));
        if (!msg) return null;

        if (msg.text.includes('VOTE: IKUT')) return 'IKUT';
        if (msg.text.includes('VOTE: TIDAK IKUT')) return 'TIDAK IKUT';
        return null;
    }

    // ─── CHAT FETCH LOGIC ───

    // Kirim pesan ke API PHP
    async function sendMessageToAI(isVote = false) {
        const session = getActiveSession();
        if (!session || isFetching) return;

        isFetching = true;
        chatInput.disabled = true;
        sendBtn.disabled = true;
        if (voteBtn) voteBtn.disabled = true;
        refreshVotePromptInFeed();

        try {
            const res = await fetch("../controllers/ai-debate.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    competition: session.competition,
                    messages: session.messages,
                    voteRequested: isVote
                })
            });

            if (!res.ok) {
                const errData = await res.json();
                throw new Error(errData.error || "Gagal menghubungi server Gemini.");
            }

            const data = await res.json();
            if (data.success && data.text) {
                const parsedMessages = parseAiResponse(data.text);
                if (parsedMessages.length > 0) {
                    if (isVote) {
                        session.isVotingDone = true;
                    }
                    queueMessages(parsedMessages);
                } else {
                    isFetching = false;
                    chatInput.disabled = false;
                    chatInput.focus();
                }
            } else {
                throw new Error("Respons dari AI kosong.");
            }
        } catch (err) {
            console.error(err);
            appendMessageBubble("STRATEGIS", `⚠️ Maaf teman-teman, koneksi internet/server terganggu (${err.message}). Coba lagi nanti ya!`, new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));
            isFetching = false;
            chatInput.disabled = false;
            chatInput.focus();
        }
    }

    // Parsing output dari Gemini API menjadi list gelembung pesan
    function parseAiResponse(text) {
        const lines = text.split('\n');
        const parsed = [];
        const regex = /^\[(REALISTIS|AMBIS|STRATEGIS|FINAL_REPORT_JSON)\]:\s*([\s\S]*)/i;

        let currentSender = null;
        let currentText = '';

        for (const line of lines) {
            const trimmed = line.trim();
            if (!trimmed) continue;

            const match = trimmed.match(regex);
            if (match) {
                if (currentSender) {
                    parsed.push({ sender: currentSender, text: currentText.trim() });
                }
                currentSender = match[1].toUpperCase();
                currentText = match[2];
            } else {
                if (currentSender) {
                    currentText += '\n' + trimmed;
                }
            }
        }

        if (currentSender) {
            parsed.push({ sender: currentSender, text: currentText.trim() });
        }

        return parsed;
    }

    // Antrean gelembung chat untuk efek typing simulator
    function queueMessages(newMessages) {
        let index = 0;

        function processNext() {
            if (index >= newMessages.length) {
                removeTypingIndicator();
                isFetching = false;
                chatInput.disabled = false;
                chatInput.focus();
                
                const session = getActiveSession();
                const userMsgCount = session.messages.filter(m => m.sender === 'USER').length;
                if (voteBtn) voteBtn.disabled = (session.isVotingDone || userMsgCount < 2);
                refreshVotePromptInFeed();
                saveSessions();
                renderHistoryList();
                return;
            }

            const nextMsg = newMessages[index];
            showTypingIndicator(nextMsg.sender);

            setTimeout(() => {
                removeTypingIndicator();

                const session = getActiveSession();
                // Hindari duplikat pesan jika user melakukan spam click
                const exists = session.messages.some(m => m.sender === nextMsg.sender && m.text === nextMsg.text);
                
                if (!exists) {
                    const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                    const msgWithTime = { ...nextMsg, time: timeStr };
                    session.messages.push(msgWithTime);

                    if (nextMsg.sender === 'FINAL_REPORT_JSON') {
                        renderFinalReportCard(nextMsg.text);
                    } else {
                        appendMessageBubble(nextMsg.sender, nextMsg.text, timeStr);
                    }
                }

                index++;
                saveSessions();

                if (index < newMessages.length) {
                    setTimeout(processNext, 850);
                } else {
                    isFetching = false;
                    chatInput.disabled = false;
                    chatInput.focus();
                    
                    const userMsgCount = session.messages.filter(m => m.sender === 'USER').length;
                    if (voteBtn) voteBtn.disabled = (session.isVotingDone || userMsgCount < 2);
                    refreshVotePromptInFeed();
                    renderHistoryList();
                }
            }, 1500);
        }

        processNext();
    }

    // ─── EVENT LISTENERS & UI INTERACTIONS ───

    function setupEventListeners() {
        // Send Button Click
        sendBtn.addEventListener("click", () => {
            handleSendMessage();
        });

        // Keydown Enter inside Input (tidak menekan Shift)
        chatInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                handleSendMessage();
            }
        });

        // Auto-resize input text area
        chatInput.addEventListener("input", () => {
            chatInput.style.height = 'auto';
            chatInput.style.height = Math.min(chatInput.scrollHeight, 130) + 'px';
        });

        // Vote Button Click
        if (voteBtn) {
            voteBtn.addEventListener("click", () => {
                if (isFetching) return;
                sendMessageToAI(true);
            });
        }

        // Reset Current Discussion
        resetBtn.addEventListener("click", () => {
            if (isFetching || !confirm("Apakah Anda yakin ingin mereset obrolan aktif ini? Semua percakapan saat ini akan dihapus.")) return;
            
            const session = getActiveSession();
            if (!session) return;

            const initialMsg = session.competition
                ? {
                    sender: 'STRATEGIS',
                    text: `Halo! Kita lihat kamu tertarik sama <strong>${session.competition.title}</strong>. Yuk kita bedah kesiapanmu dari berbagai sudut pandang! 🎯`,
                    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                  }
                : {
                    sender: 'AMBIS',
                    text: `Halo! Ada info lomba seru dari luar ya? Coba ceritain di sini dong nama lomba, biaya, dan hadiahnya, biar kita berdebat kelayakanmu!`,
                    time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
                  };

            session.messages = [initialMsg];
            session.isVotingDone = false;
            saveSessions();
            renderActiveSession();
        });

        // Sidebar Toggle Mobile
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.add("open");
            sidebarOverlay.classList.add("show");
        });

        sidebarOverlay.addEventListener("click", () => {
            closeSidebarMobile();
        });

        // Modal Open "Diskusi Baru"
        newChatBtn.addEventListener("click", () => {
            openPickerModal();
        });

        closeModalBtn.addEventListener("click", () => {
            closePickerModal();
        });

        // Search Lomba di Modal
        modalSearchInput.addEventListener("input", (e) => {
            const query = e.target.value.toLowerCase();
            renderPickerList(query);
        });

        // Memulai Diskusi Kasual dari Modal Picker
        externalChatBtn.addEventListener("click", () => {
            closePickerModal();
            const newSess = createNewSessionObject(null);
            sessions.unshift(newSess);
            activeSessionId = newSess.id;
            saveSessions();
            renderHistoryList();
            renderActiveSession();
        });
    }

    function handleSendMessage() {
        const text = chatInput.value.trim();
        const session = getActiveSession();

        if (!text || isFetching || !session || session.isVotingDone) return;

        // Reset input height
        chatInput.value = "";
        chatInput.style.height = "auto";

        const timeStr = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        const userMsg = { sender: 'USER', text: text, time: timeStr };
        
        session.messages.push(userMsg);
        saveSessions();
        renderHistoryList();
        
        appendMessageBubble('USER', text, timeStr);
        sendMessageToAI(false);
    }

    function deleteSession(id) {
        sessions = sessions.filter(s => s.id !== id);
        
        if (sessions.length === 0) {
            const defaultSess = createNewSessionObject(null);
            sessions = [defaultSess];
        }

        saveSessions();
        
        if (activeSessionId === id) {
            activeSessionId = sessions[0].id;
        }

        renderHistoryList();
        renderActiveSession();
    }

    function closeSidebarMobile() {
        sidebar.classList.remove("open");
        sidebarOverlay.classList.remove("show");
    }

    function scrollChatToBottom() {
        chatFeed.scrollTop = chatFeed.scrollHeight;
    }

    // Modal Picker Handler
    function openPickerModal() {
        pickerModal.style.display = "flex";
        modalSearchInput.value = "";
        renderPickerList("");
        modalSearchInput.focus();
    }

    function closePickerModal() {
        pickerModal.style.display = "none";
    }

    // Render List Lomba di Picker Modal
    function renderPickerList(filterText = "") {
        modalList.innerHTML = "";
        
        // window.dbCompetitions adalah JSON yang dicetak oleh PHP
        const list = window.dbCompetitions || [];
        const filtered = list.filter(c => c.title.toLowerCase().includes(filterText));

        if (filtered.length === 0) {
            modalList.innerHTML = `<div style="text-align:center; padding:24px; color:var(--text-ter); font-size:12px;">Tidak ada lomba aktif yang cocok.</div>`;
            return;
        }

        filtered.forEach(c => {
            const item = document.createElement("div");
            item.className = "comp-list-item";
            
            // Format image
            const imgPath = c.image ? `../assets/images/${c.image}` : "../assets/images/default.jpg";
            
            item.innerHTML = `
                <div class="comp-thumb">
                    <img src="${imgPath}" alt="${c.title}">
                </div>
                <div class="comp-item-info">
                    <div class="comp-item-title">${c.title}</div>
                    <div class="comp-item-tags">
                        <span class="comp-tag"><span class="mat">payments</span> ${parseInt(c.registration_fee) > 0 ? 'Berbayar' : 'Gratis'}</span>
                        <span class="comp-tag"><span class="mat">workspace_premium</span> ${c.category || 'Umum'}</span>
                    </div>
                </div>
            `;

            item.addEventListener("click", () => {
                closePickerModal();
                selectCompetitionForChat(c);
            });

            modalList.appendChild(item);
        });
    }

    // Memicu Obrolan Baru dengan Lomba dari Database
    async function selectCompetitionForChat(comp) {
        // Cek jika sudah pernah berdiskusi lomba ini sebelumnya
        const existing = sessions.find(s => s.competition && String(s.competition.id) === String(comp.id));
        if (existing) {
            activeSessionId = existing.id;
            renderHistoryList();
            renderActiveSession();
            return;
        }

        // Buat sesi baru
        const newSess = createNewSessionObject(comp);
        sessions.unshift(newSess);
        activeSessionId = newSess.id;
        saveSessions();
        renderHistoryList();
        renderActiveSession();

        // Trigger AI respons awal membahas lomba
        isFetching = true;
        chatInput.disabled = true;
        sendBtn.disabled = true;
        if (voteBtn) voteBtn.disabled = true;
        refreshVotePromptInFeed();
        showTypingIndicator('REALISTIS');

        try {
            const res = await fetch("../controllers/ai-debate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    competition: comp,
                    messages: newSess.messages,
                    voteRequested: false
                })
            });

            if (!res.ok) {
                const errData = await res.json();
                throw new Error(errData.error || "Gagal menghubungi server Gemini.");
            }

            const data = await res.json();
            if (data.success && data.text) {
                const parsed = parseAiResponse(data.text);
                if (parsed.length > 0) {
                    queueMessages(parsed);
                } else {
                    isFetching = false;
                    chatInput.disabled = false;
                }
            } else {
                throw new Error("Respons awal dari AI kosong.");
            }
        } catch (err) {
            console.error(err);
            appendMessageBubble("REALISTIS", `⚠️ Maaf, gagal memicu respons awal AI (${err.message}). Coba ketik pesan apa saja untuk memulai percakapan.`, new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));
            isFetching = false;
            chatInput.disabled = false;
            chatInput.focus();
        }
    }

    // Helper sidebar icons
    function getHistoryIcon(comp) {
        if (!comp) return '🤖';
        const cat = (comp.category || '').toLowerCase();
        if (cat.includes('desain') || cat.includes('poster') || cat.includes('seni') || cat.includes('art')) return '🎨';
        if (cat.includes('hackathon') || cat.includes('teknologi') || cat.includes('programming') || cat.includes('web') || cat.includes('ui/ux') || cat.includes('coding')) return '💻';
        if (cat.includes('esai') || cat.includes('akademik') || cat.includes('ilmiah') || cat.includes('tulis') || cat.includes('paper')) return '📝';
        if (cat.includes('video') || cat.includes('film') || cat.includes('fotografi') || cat.includes('photo') || cat.includes('cinema')) return '🎬';
        if (cat.includes('bisnis') || cat.includes('business') || cat.includes('plan') || cat.includes('marketing')) return '📊';
        return '🏆';
    }

    function getHistoryMeta(sessionItem) {
        const msgCount = sessionItem.messages.filter(m => m.sender !== 'FINAL_REPORT_JSON').length;
        const date = new Date(sessionItem.createdAt);
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        let relativeDate = '';
        if (date.toDateString() === today.toDateString()) {
            relativeDate = 'Hari ini';
        } else if (date.toDateString() === yesterday.toDateString()) {
            relativeDate = 'Kemarin';
        } else {
            relativeDate = date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        }

        return `${relativeDate} · ${msgCount} pesan`;
    }
});
