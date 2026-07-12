document.addEventListener('DOMContentLoaded', () => {
    let questions = window.QUIZ_INITIAL_DATA || [];
    // Ensure all have uids
    questions.forEach((q, i) => q.uid = i);
    let uidCounter = questions.length;

    const container = document.getElementById('questionsContainer');
    const noQuestionsMsg = document.getElementById('noQuestionsMsg');
    const submitBtn = document.getElementById('submitBtn');
    const questionCountText = document.getElementById('questionCountText');

    const addLetterBtn = document.getElementById('addLetterBtn');
    const addPronunciationBtn = document.getElementById('addPronunciationBtn');
    const addVoiceBtn = document.getElementById('addVoiceBtn');

    if (addLetterBtn) addLetterBtn.addEventListener('click', () => addQuestion('letter'));
    if (addPronunciationBtn) addPronunciationBtn.addEventListener('click', () => addQuestion('pronunciation'));
    if (addVoiceBtn) addVoiceBtn.addEventListener('click', () => addQuestion('voice'));

    function addQuestion(type) {
        questions.push({
            uid: uidCounter++,
            type: type,
            question_text: '',
            options: type !== 'voice' ? [
                { text: '', is_correct: true },
                { text: '', is_correct: false },
                { text: '', is_correct: false },
                { text: '', is_correct: false },
            ] : []
        });
        render();
    }

    window.removeQuestion = function(idx) {
        if (typeof confirmAsync === 'function') {
            confirmAsync('Are you sure you want to delete this question?').then(confirmed => {
                if (confirmed) {
                    questions.splice(idx, 1);
                    render();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this question?')) {
                questions.splice(idx, 1);
                render();
            }
        }
    };

    window.setCorrect = function(qIdx, optIdx) {
        questions[qIdx].options.forEach((opt, i) => {
            opt.is_correct = (i === optIdx);
        });
        render();
    };

    window.updateQuestionType = function(idx, val) {
        questions[idx].type = val;
        if (val === 'voice') {
            questions[idx].options = [];
        } else if (!questions[idx].options || questions[idx].options.length === 0) {
            questions[idx].options = [
                { text: '', is_correct: true },
                { text: '', is_correct: false },
                { text: '', is_correct: false },
                { text: '', is_correct: false },
            ];
        }
        render();
    };

    window.updateQuestionText = function(idx, val) {
        questions[idx].question_text = val;
    };

    window.updateOptionText = function(qIdx, optIdx, val) {
        questions[qIdx].options[optIdx].text = val;
    };

    function render() {
        if (!container) return;
        
        if (questionCountText) questionCountText.textContent = `${questions.length} Questions`;
        if (questions.length === 0) {
            if (noQuestionsMsg) noQuestionsMsg.style.display = 'block';
            if (submitBtn) submitBtn.disabled = true;
        } else {
            if (noQuestionsMsg) noQuestionsMsg.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
        }

        container.innerHTML = '';
        questions.forEach((q, idx) => {
            const typeLabel = q.type === 'voice' ? 'Arabic Paragraph' : (q.type === 'letter' ? 'Arabic Letter' : 'Arabic Word/Sentence');
            const rowRows = q.type === 'voice' ? 4 : 2;

            let optionsHtml = '';
            if (q.type !== 'voice') {
                let optsInner = '';
                q.options.forEach((opt, oi) => {
                    const bgClass = opt.is_correct ? 'border-[#059669] bg-[#ecfdf5]' : 'bg-[#f8fafc]';
                    const checkedStr = opt.is_correct ? 'checked' : '';
                    const safeOptText = opt.text.replace(/"/g, '&quot;');
                    optsInner += `
                        <div class="flex items-center gap-2.5 p-3 border border-gray-200 rounded-xl ${bgClass}">
                            <input type="radio" name="questions[${idx}][correct]" value="${oi}"
                                ${checkedStr} onchange="setCorrect(${idx}, ${oi})"
                                class="w-4 h-4 text-[#059669] focus:ring-[#059669]" />
                            <input type="text" name="questions[${idx}][options][${oi}][text]"
                                value="${safeOptText}"
                                oninput="updateOptionText(${idx}, ${oi}, this.value)"
                                placeholder="Option ${oi + 1}" required
                                class="flex-1 bg-transparent text-sm font-medium text-[#1e293b] focus:outline-none placeholder-gray-300" />
                            <input type="hidden" name="questions[${idx}][options][${oi}][is_correct]"
                                value="${opt.is_correct ? 1 : 0}" />
                        </div>
                    `;
                });

                optionsHtml = `
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-[#374151]">Answer Options <span
                                    class="text-red-500">*</span></label>
                            <span class="text-xs text-[#64748b]">Mark one as correct</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${optsInner}
                        </div>
                    </div>
                `;
            }

            let voiceInfoHtml = '';
            if (q.type === 'voice') {
                voiceInfoHtml = `
                    <div class="flex items-center gap-2 text-xs text-[#64748b] bg-[#f0fdf4] px-4 py-2.5 rounded-xl border border-[#d1fae5]">
                        <svg class="w-4 h-4 text-[#059669] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        The user will read this paragraph and record their voice for up to 1 minute.
                    </div>
                `;
            }

            const isLetterSelected = q.type === 'letter' ? 'selected' : '';
            const isPronunSelected = q.type === 'pronunciation' ? 'selected' : '';
            const isVoiceSelected = q.type === 'voice' ? 'selected' : '';
            const safeQText = q.question_text.replace(/</g, '&lt;').replace(/>/g, '&gt;');

            const qHtml = `
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-5 py-3 bg-[#f8fafc] border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 flex items-center justify-center bg-[#059669] text-white text-xs font-bold rounded-lg">${idx + 1}</span>
                            <select name="questions[${idx}][type]" onchange="updateQuestionType(${idx}, this.value)"
                                class="text-sm font-semibold border border-gray-200 rounded-lg px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#059669]/30">
                                <option value="letter" ${isLetterSelected}>Alphabet (Letter)</option>
                                <option value="pronunciation" ${isPronunSelected}>Pronunciation (Pronunciation)</option>
                                <option value="voice" ${isVoiceSelected}>Voice Recording</option>
                            </select>
                        </div>
                        <button type="button" onclick="removeQuestion(${idx})"
                            class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-[#374151]">
                                <span>${typeLabel}</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea name="questions[${idx}][question_text]" oninput="updateQuestionText(${idx}, this.value)"
                                rows="${rowRows}" required placeholder="Write Arabic (RTL)..."
                                dir="rtl"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-lg focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none font-arabic text-right"
                                style="font-family: 'Amiri', 'Noto Naskh Arabic', serif;">${safeQText}</textarea>
                        </div>
                        ${optionsHtml}
                        ${voiceInfoHtml}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', qHtml);
        });
    }

    // Initial render
    if (container) {
        render();
    }
});
