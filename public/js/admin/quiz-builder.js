/**
 * Unified Quiz & Exam Builder (Vanilla JS)
 * Supports Quiz vs Exam settings, custom registration fields, question marks, and multiple option formats.
 */
document.addEventListener('DOMContentLoaded', () => {
    let questions = window.QUIZ_INITIAL_DATA || [];
    let customFields = window.QUIZ_CUSTOM_FIELDS || [];

    // Ensure all questions have unique uids
    questions.forEach((q, i) => {
        q.uid = i;
        if (q.marks === undefined || q.marks === null) {
            q.marks = 1.00;
        }
        if (!q.options && q.type !== 'voice') {
            q.options = [];
        }
    });
    let uidCounter = questions.length;

    // DOM Elements
    const container = document.getElementById('questionsContainer');
    const noQuestionsMsg = document.getElementById('noQuestionsMsg');
    const submitBtn = document.getElementById('submitBtn');
    const questionCountText = document.getElementById('questionCountText');
    const totalMarksText = document.getElementById('totalMarksText');

    // Type Toggle Elements
    const typeSelect = document.getElementById('quizTypeSelect');
    const examSettingsBox = document.getElementById('examSettingsBox');

    // Custom Fields Elements
    const customFieldsContainer = document.getElementById('customFieldsContainer');
    const addCustomFieldBtn = document.getElementById('addCustomFieldBtn');

    // Add Question Buttons
    const addMcqBtn = document.getElementById('addMcqBtn');
    const addMultiChoiceBtn = document.getElementById('addMultiChoiceBtn');
    const addLetterBtn = document.getElementById('addLetterBtn');
    const addPronunciationBtn = document.getElementById('addPronunciationBtn');
    const addVoiceBtn = document.getElementById('addVoiceBtn');

    if (addMcqBtn) addMcqBtn.addEventListener('click', () => addQuestion('mcq'));
    if (addMultiChoiceBtn) addMultiChoiceBtn.addEventListener('click', () => addQuestion('multiple_choice'));
    if (addLetterBtn) addLetterBtn.addEventListener('click', () => addQuestion('letter'));
    if (addPronunciationBtn) addPronunciationBtn.addEventListener('click', () => addQuestion('pronunciation'));
    if (addVoiceBtn) addVoiceBtn.addEventListener('click', () => addQuestion('voice'));

    // Handle Type Change (Quiz vs Exam)
    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            updateTypeView(typeSelect.value);
        });
        updateTypeView(typeSelect.value);
    }

    function updateTypeView(type) {
        if (!examSettingsBox) return;
        const examBadge = document.getElementById('examBadge');
        if (type === 'exam') {
            examSettingsBox.classList.remove('hidden');
            if (examBadge) examBadge.textContent = 'Exam Mode';
        } else {
            examSettingsBox.classList.remove('hidden'); // keep accessible with friendly defaults
            if (examBadge) examBadge.textContent = 'Quiz Mode';
        }
    }

    // ─────────────────────────────────────────────────────────────
    // CUSTOM FIELDS HANDLER
    // ─────────────────────────────────────────────────────────────
    if (addCustomFieldBtn) {
        addCustomFieldBtn.addEventListener('click', () => {
            customFields.push({
                key: '',
                label: '',
                type: 'text',
                required: false
            });
            renderCustomFields();
        });
    }

    window.removeCustomField = function(idx) {
        customFields.splice(idx, 1);
        renderCustomFields();
    };

    window.updateCustomField = function(idx, prop, val) {
        if (prop === 'key') {
            val = val.toLowerCase().replace(/[^a-z0-9_]/g, '');
        }
        customFields[idx][prop] = val;
    };

    function renderCustomFields() {
        if (!customFieldsContainer) return;
        customFieldsContainer.innerHTML = '';

        if (customFields.length === 0) {
            customFieldsContainer.innerHTML = `
                <div class="text-xs text-gray-400 py-2 italic text-center border border-dashed border-gray-200 rounded-xl">
                    No custom fields added (click the button below to add)
                </div>
            `;
            return;
        }

        customFields.forEach((cf, idx) => {
            const html = `
                <div class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-xl mb-2">
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Field Label</label>
                        <input type="text" name="custom_fields[${idx}][label]" value="${escapeHtml(cf.label || '')}"
                            placeholder="e.g. Roll No, Batch" required
                            oninput="updateCustomField(${idx}, 'label', this.value)"
                            class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-[#059669]">
                    </div>
                    <div class="w-36">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Field Key (Unique)</label>
                        <input type="text" name="custom_fields[${idx}][key]" value="${escapeHtml(cf.key || '')}"
                            placeholder="roll_no, batch" required
                            oninput="updateCustomField(${idx}, 'key', this.value)"
                            class="w-full px-3 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 font-mono focus:outline-none focus:ring-1 focus:ring-[#059669]">
                    </div>
                    <div class="w-28">
                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Type</label>
                        <select name="custom_fields[${idx}][type]" onchange="updateCustomField(${idx}, 'type', this.value)"
                            class="w-full px-2 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none">
                            <option value="text" ${cf.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="number" ${cf.type === 'number' ? 'selected' : ''}>Number</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-1.5 pt-5">
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" name="custom_fields[${idx}][required]" value="1"
                                ${cf.required ? 'checked' : ''} onchange="updateCustomField(${idx}, 'required', this.checked)"
                                class="w-4 h-4 text-[#059669] rounded focus:ring-[#059669]">
                            <span class="text-xs font-medium text-gray-600">Required</span>
                        </label>
                        <button type="button" onclick="removeCustomField(${idx})"
                            class="ml-2 w-7 h-7 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            customFieldsContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // QUESTIONS & OPTIONS HANDLER
    // ─────────────────────────────────────────────────────────────
    function addQuestion(type) {
        questions.push({
            uid: uidCounter++,
            type: type,
            question_text: '',
            marks: 1.00,
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
        if (confirm('Are you sure you want to delete this question?')) {
            questions.splice(idx, 1);
            render();
        }
    };

    window.addOption = function(qIdx) {
        if (!questions[qIdx].options) questions[qIdx].options = [];
        questions[qIdx].options.push({ text: '', is_correct: false });
        render();
    };

    window.removeOption = function(qIdx, optIdx) {
        if (questions[qIdx].options.length <= 2) {
            alert('At least 2 options are required.');
            return;
        }
        questions[qIdx].options.splice(optIdx, 1);
        render();
    };

    window.setCorrectSingle = function(qIdx, optIdx) {
        questions[qIdx].options.forEach((opt, i) => {
            opt.is_correct = (i === optIdx);
        });
        render();
    };

    window.toggleCorrectMultiple = function(qIdx, optIdx, isChecked) {
        questions[qIdx].options[optIdx].is_correct = isChecked;
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

    window.updateQuestionMarks = function(idx, val) {
        const num = parseFloat(val);
        questions[idx].marks = isNaN(num) || num < 0 ? 0 : num;
        calculateTotalMarks();
    };

    window.updateQuestionText = function(idx, val) {
        questions[idx].question_text = val;
    };

    window.updateOptionText = function(qIdx, optIdx, val) {
        questions[qIdx].options[optIdx].text = val;
    };

    function calculateTotalMarks() {
        let total = 0;
        questions.forEach(q => {
            total += parseFloat(q.marks || 0);
        });
        if (totalMarksText) {
            totalMarksText.textContent = `${total.toFixed(2)} Marks`;
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function render() {
        if (!container) return;

        calculateTotalMarks();

        if (questionCountText) {
            questionCountText.textContent = `${questions.length} Questions`;
        }

        if (questions.length === 0) {
            if (noQuestionsMsg) noQuestionsMsg.style.display = 'block';
            if (submitBtn) submitBtn.disabled = true;
        } else {
            if (noQuestionsMsg) noQuestionsMsg.style.display = 'none';
            if (submitBtn) submitBtn.disabled = false;
        }

        container.innerHTML = '';
        questions.forEach((q, idx) => {
            const isVoice = q.type === 'voice';
            const isMultiSelect = q.type === 'multiple_choice';
            const isArabic = (q.type === 'letter' || q.type === 'pronunciation');

            const typeLabel = isVoice
                ? 'Voice Paragraph'
                : (q.type === 'letter' ? 'Arabic Letter' : (q.type === 'pronunciation' ? 'Word / Pronunciation' : 'Question Text'));

            let optionsHtml = '';
            if (!isVoice) {
                let optsInner = '';
                (q.options || []).forEach((opt, oi) => {
                    const isChecked = !!opt.is_correct;
                    const bgClass = isChecked ? 'border-[#059669] bg-[#ecfdf5]' : 'bg-[#f8fafc]';
                    const safeOptText = escapeHtml(opt.text || '');

                    const inputControl = isMultiSelect
                        ? `<input type="checkbox" name="questions[${idx}][options][${oi}][is_correct]" value="1"
                                ${isChecked ? 'checked' : ''} onchange="toggleCorrectMultiple(${idx}, ${oi}, this.checked)"
                                class="w-4 h-4 text-[#059669] rounded focus:ring-[#059669] cursor-pointer" />`
                        : `<input type="radio" name="questions[${idx}][correct_radio]" value="${oi}"
                                ${isChecked ? 'checked' : ''} onchange="setCorrectSingle(${idx}, ${oi})"
                                class="w-4 h-4 text-[#059669] focus:ring-[#059669] cursor-pointer" />
                           <input type="hidden" name="questions[${idx}][options][${oi}][is_correct]" value="${isChecked ? 1 : 0}" />`;

                    optsInner += `
                        <div class="flex items-center gap-2 p-2.5 border border-gray-200 rounded-xl ${bgClass} transition-all">
                            ${inputControl}
                            <input type="text" name="questions[${idx}][options][${oi}][text]"
                                value="${safeOptText}"
                                oninput="updateOptionText(${idx}, ${oi}, this.value)"
                                placeholder="Option ${oi + 1}" required
                                class="flex-1 bg-transparent text-sm font-medium text-[#1e293b] focus:outline-none placeholder-gray-300" />
                            ${q.options.length > 2 ? `
                                <button type="button" onclick="removeOption(${idx}, ${oi})"
                                    class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-red-500 rounded-md">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            ` : ''}
                        </div>
                    `;
                });

                optionsHtml = `
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-[#374151] uppercase tracking-wider">
                                Answer Options <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-[#64748b]">
                                ${isMultiSelect ? 'Multiple correct answers can be selected' : 'Select the single correct answer with the radio button'}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${optsInner}
                        </div>
                        <button type="button" onclick="addOption(${idx})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-[#059669] bg-[#ecfdf5] hover:bg-[#d1fae5] rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            + Add Option
                        </button>
                    </div>
                `;
            }

            let voiceInfoHtml = '';
            if (isVoice) {
                voiceInfoHtml = `
                    <div class="flex items-center gap-2 text-xs text-[#059669] bg-[#ecfdf5] px-4 py-3 rounded-xl border border-[#d1fae5]">
                        <svg class="w-4 h-4 text-[#059669] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                        </svg>
                        The candidate will read this passage aloud and record up to 1 minute of audio. Admins can evaluate and provide voice feedback afterwards.
                    </div>
                `;
            }

            const safeQText = escapeHtml(q.question_text || '');
            const direction = isArabic ? 'dir="rtl"' : 'dir="auto"';
            const fontClass = isArabic ? 'font-arabic text-right' : '';

            const qHtml = `
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden mb-4">
                    <div class="flex items-center justify-between px-5 py-3 bg-[#f8fafc] border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 flex items-center justify-center bg-[#059669] text-white text-xs font-bold rounded-lg">${idx + 1}</span>
                            <select name="questions[${idx}][type]" onchange="updateQuestionType(${idx}, this.value)"
                                class="text-xs font-bold border border-gray-200 rounded-lg px-2.5 py-1.5 bg-white text-gray-700 focus:outline-none focus:ring-1 focus:ring-[#059669]">
                                <option value="mcq" ${q.type === 'mcq' ? 'selected' : ''}>MCQ (Single Choice)</option>
                                <option value="multiple_choice" ${q.type === 'multiple_choice' ? 'selected' : ''}>Multiple Choice (Multi Select)</option>
                                <option value="letter" ${q.type === 'letter' ? 'selected' : ''}>Arabic Alphabet</option>
                                <option value="pronunciation" ${q.type === 'pronunciation' ? 'selected' : ''}>Pronunciation</option>
                                <option value="voice" ${q.type === 'voice' ? 'selected' : ''}>Voice Recording</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-2.5 py-1">
                                <span class="text-xs font-semibold text-gray-500">Marks:</span>
                                <input type="number" step="0.5" min="0" max="100" name="questions[${idx}][marks]"
                                    value="${parseFloat(q.marks || 1.00)}"
                                    oninput="updateQuestionMarks(${idx}, this.value)"
                                    class="w-14 text-xs font-bold text-[#059669] text-center focus:outline-none">
                            </div>
                            <button type="button" onclick="removeQuestion(${idx})"
                                class="w-7 h-7 flex items-center justify-center rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium text-[#374151]">
                                <span>${typeLabel}</span>
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea name="questions[${idx}][question_text]" oninput="updateQuestionText(${idx}, this.value)"
                                rows="${isVoice ? 4 : 2}" required placeholder="Write question content..."
                                ${direction}
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-base focus:outline-none focus:ring-2 focus:ring-[#059669]/30 focus:border-[#059669] resize-none ${fontClass}">${safeQText}</textarea>
                        </div>
                        ${optionsHtml}
                        ${voiceInfoHtml}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', qHtml);
        });
    }

    // Initial renders
    renderCustomFields();
    if (container) {
        render();
    }
});
