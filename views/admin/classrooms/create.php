<?php
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

// Prepare JSON datasets for Alpine.js
$teachersJson  = json_encode(array_values($teachers), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';
$studentsJson  = json_encode(array_values($students), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';
$observersJson = json_encode(array_values($eligibleObservers), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';
?>

<script nonce="<?= csp_nonce() ?>">
function classroomCreateManager() {
    return {
        // Modal Control: 'teachers' | 'students' | 'observers' | null
        activeModal: null,

        // Master Directory Data
        teachersList: <?= $teachersJson ?>,
        studentsList: <?= $studentsJson ?>,
        observersList: <?= $observersJson ?>,

        // Selected ID State
        selectedTeachers: [],
        primaryTeacher: null,
        selectedStudents: [],
        selectedObservers: [],

        // Teacher Modal Filter & Pagination
        teacherSearch: '',
        teacherTab: 'all', // 'all' | 'selected'
        teacherPage: 1,
        teacherPerPage: 10,

        // Student Modal Filter & Pagination
        studentSearch: '',
        studentTab: 'all', // 'all' | 'selected'
        studentPage: 1,
        studentPerPage: 10,

        // Observer Modal Filter & Pagination
        observerSearch: '',
        observerTab: 'all', // 'all' | 'selected'
        observerPage: 1,
        observerPerPage: 10,

        // ---------------------------------------------------------------------
        // TEACHER METHODS
        // ---------------------------------------------------------------------
        filteredTeachers() {
            let list = this.teachersList;
            if (this.teacherTab === 'selected') {
                list = list.filter(t => this.selectedTeachers.includes(parseInt(t.id)));
            }
            if (this.teacherSearch.trim() !== '') {
                const q = this.teacherSearch.toLowerCase();
                list = list.filter(t => 
                    (t.username && t.username.toLowerCase().includes(q)) || 
                    (t.email && t.email.toLowerCase().includes(q))
                );
            }
            return list;
        },
        paginatedTeachers() {
            const list = this.filteredTeachers();
            const start = (this.teacherPage - 1) * this.teacherPerPage;
            return list.slice(start, start + this.teacherPerPage);
        },
        totalTeacherPages() {
            return Math.ceil(this.filteredTeachers().length / this.teacherPerPage) || 1;
        },
        toggleTeacher(id) {
            id = parseInt(id);
            const idx = this.selectedTeachers.indexOf(id);
            if (idx > -1) {
                this.selectedTeachers.splice(idx, 1);
                if (this.primaryTeacher === id) {
                    this.primaryTeacher = this.selectedTeachers.length > 0 ? this.selectedTeachers[0] : null;
                }
            } else {
                this.selectedTeachers.push(id);
                if (!this.primaryTeacher) {
                    this.primaryTeacher = id;
                }
            }
        },
        selectAllFilteredTeachers() {
            const filtered = this.filteredTeachers();
            filtered.forEach(t => {
                const id = parseInt(t.id);
                if (!this.selectedTeachers.includes(id)) {
                    this.selectedTeachers.push(id);
                }
            });
            if (!this.primaryTeacher && this.selectedTeachers.length > 0) {
                this.primaryTeacher = this.selectedTeachers[0];
            }
        },
        clearAllTeachers() {
            this.selectedTeachers = [];
            this.primaryTeacher = null;
        },
        setPrimaryTeacher(id) {
            this.primaryTeacher = parseInt(id);
        },
        getSelectedTeacherObjects() {
            return this.teachersList.filter(t => this.selectedTeachers.includes(parseInt(t.id)));
        },

        // ---------------------------------------------------------------------
        // STUDENT METHODS
        // ---------------------------------------------------------------------
        filteredStudents() {
            let list = this.studentsList;
            if (this.studentTab === 'selected') {
                list = list.filter(s => this.selectedStudents.includes(parseInt(s.id)));
            }
            if (this.studentSearch.trim() !== '') {
                const q = this.studentSearch.toLowerCase();
                list = list.filter(s => 
                    (s.username && s.username.toLowerCase().includes(q)) || 
                    (s.email && s.email.toLowerCase().includes(q))
                );
            }
            return list;
        },
        paginatedStudents() {
            const list = this.filteredStudents();
            const start = (this.studentPage - 1) * this.studentPerPage;
            return list.slice(start, start + this.studentPerPage);
        },
        totalStudentPages() {
            return Math.ceil(this.filteredStudents().length / this.studentPerPage) || 1;
        },
        toggleStudent(id) {
            id = parseInt(id);
            const idx = this.selectedStudents.indexOf(id);
            if (idx > -1) {
                this.selectedStudents.splice(idx, 1);
            } else {
                this.selectedStudents.push(id);
            }
        },
        selectAllFilteredStudents() {
            const filtered = this.filteredStudents();
            filtered.forEach(s => {
                const id = parseInt(s.id);
                if (!this.selectedStudents.includes(id)) {
                    this.selectedStudents.push(id);
                }
            });
        },
        clearAllStudents() {
            this.selectedStudents = [];
        },
        getSelectedStudentObjects() {
            return this.studentsList.filter(s => this.selectedStudents.includes(parseInt(s.id)));
        },

        // ---------------------------------------------------------------------
        // OBSERVER METHODS
        // ---------------------------------------------------------------------
        filteredObservers() {
            let list = this.observersList;
            if (this.observerTab === 'selected') {
                list = list.filter(o => this.selectedObservers.includes(parseInt(o.id)));
            }
            if (this.observerSearch.trim() !== '') {
                const q = this.observerSearch.toLowerCase();
                list = list.filter(o => 
                    (o.username && o.username.toLowerCase().includes(q)) || 
                    (o.email && o.email.toLowerCase().includes(q))
                );
            }
            return list;
        },
        paginatedObservers() {
            const list = this.filteredObservers();
            const start = (this.observerPage - 1) * this.observerPerPage;
            return list.slice(start, start + this.observerPerPage);
        },
        totalObserverPages() {
            return Math.ceil(this.filteredObservers().length / this.observerPerPage) || 1;
        },
        toggleObserver(id) {
            id = parseInt(id);
            const idx = this.selectedObservers.indexOf(id);
            if (idx > -1) {
                this.selectedObservers.splice(idx, 1);
            } else {
                this.selectedObservers.push(id);
            }
        },
        selectAllFilteredObservers() {
            const filtered = this.filteredObservers();
            filtered.forEach(o => {
                const id = parseInt(o.id);
                if (!this.selectedObservers.includes(id)) {
                    this.selectedObservers.push(id);
                }
            });
        },
        clearAllObservers() {
            this.selectedObservers = [];
        },
        getSelectedObserverObjects() {
            return this.observersList.filter(o => this.selectedObservers.includes(parseInt(o.id)));
        },

        // Form Submit Validation
        submitForm(e) {
            if (this.selectedTeachers.length === 0) {
                e.preventDefault();
                alert('Please assign at least one Teacher to the classroom.');
                this.activeModal = 'teachers';
                return;
            }
            if (this.selectedStudents.length === 0) {
                e.preventDefault();
                alert('Please assign at least one Student to the classroom.');
                this.activeModal = 'students';
                return;
            }
        },

        init() {
            this.$watch('activeModal', value => {
                if (value) {
                    document.body.classList.add('overflow-hidden');
                } else {
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }
    };
}
</script>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]" x-data="classroomCreateManager()" @keydown.escape.window="activeModal = null">

    <div class="max-w-5xl mx-auto space-y-6">
        
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Create Classroom</h1>
                <p class="mt-1 text-sm text-gray-500">Add a new group classroom, assign multiple teachers, enroll students, and designate observers.</p>
            </div>
            <a href="/admin/classrooms/index.php" class="inline-flex items-center text-sm font-semibold text-primary hover:text-green-700 transition-colors">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Classrooms
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Main Form Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6 sm:p-8">
            <form action="" method="POST" class="space-y-8" id="createClassroomForm" @submit="submitForm($event)">
                <input type="hidden" name="csrf_token" value="<?= e(generateCsrfToken()) ?>">
                
                <!-- Hidden inputs for seamless POST compatibility -->
                <template x-for="tId in selectedTeachers" :key="'post-t-' + tId">
                    <input type="hidden" name="teacher_ids[]" :value="tId">
                </template>
                <input type="hidden" name="primary_teacher_id" :value="primaryTeacher">

                <template x-for="sId in selectedStudents" :key="'post-s-' + sId">
                    <input type="hidden" name="student_ids[]" :value="sId">
                </template>

                <template x-for="oId in selectedObservers" :key="'post-o-' + oId">
                    <input type="hidden" name="observer_ids[]" :value="oId">
                </template>

                <div class="space-y-6">
                    <!-- Class Name -->
                    <div class="space-y-1.5">
                        <label for="class_name" class="block text-sm font-semibold text-gray-800">Class Name <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                <div class="h-8 w-8 rounded-lg bg-green-50 flex items-center justify-center text-primary">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <input type="text" name="class_name" id="class_name" required placeholder="e.g. Batch 04 - Intermediate Arabic" class="block w-full pl-12 pr-3 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors text-gray-900">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">The specific identifier or batch name.</p>
                    </div>

                    <!-- Class Title -->
                    <div class="space-y-1.5">
                        <label for="class_title" class="block text-sm font-semibold text-gray-800">Class Title <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                <div class="h-8 w-8 rounded-lg bg-green-50 flex items-center justify-center text-primary">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <input type="text" name="class_title" id="class_title" required placeholder="e.g. Quranic Arabic Grammar & Fluency" class="block w-full pl-12 pr-3 py-3 border border-gray-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent sm:text-sm transition-colors text-gray-900">
                        </div>
                        <p class="mt-1 text-xs text-gray-400">The subject or descriptive title of the class.</p>
                    </div>

                    <!-- 1. ASSIGN TEACHERS -->
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">
                                    Assign Teachers <span class="text-red-500">*</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-0.5">Select instructors for this class. Designated Primary Host receives the Zoom start URL.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="selectedTeachers.length > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                                    <span x-text="selectedTeachers.length">0</span> Teacher(s) Selected
                                </span>
                                <button type="button" @click="activeModal = 'teachers'; teacherSearch = ''; teacherTab = 'all'; teacherPage = 1"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Select Teachers</span>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State Box -->
                        <div x-show="selectedTeachers.length === 0" 
                             @click="activeModal = 'teachers'; teacherSearch = ''; teacherTab = 'all'; teacherPage = 1" 
                             class="cursor-pointer border-2 border-dashed border-gray-200 hover:border-primary/50 hover:bg-emerald-50/10 rounded-xl p-5 text-center transition-all group">
                            <svg class="w-8 h-8 mx-auto text-gray-300 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            <p class="text-xs font-semibold text-gray-700 mt-2">No teachers selected yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click here to open the teacher directory and assign instructors.</p>
                        </div>

                        <!-- Selected Teachers Table -->
                        <div x-show="selectedTeachers.length > 0" class="overflow-x-auto rounded-xl border border-gray-200 shadow-2xs bg-white">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/80">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Teacher Name & Email</th>
                                        <th class="px-4 py-2.5 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Live Class Host Role</th>
                                        <th class="px-4 py-2.5 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Remove</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-xs">
                                    <template x-for="t in getSelectedTeacherObjects()" :key="'sel-t-' + t.id">
                                        <tr class="hover:bg-gray-50/60 transition-colors">
                                            <td class="px-4 py-2.5">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                        <span x-text="t.username.substring(0, 1).toUpperCase()">T</span>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-gray-900" x-text="t.username"></p>
                                                        <p class="text-[11px] text-gray-400" x-text="t.email"></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-center">
                                                <button type="button" @click="setPrimaryTeacher(t.id)" 
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold transition-all cursor-pointer"
                                                        :class="primaryTeacher === parseInt(t.id) ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                                    <span x-show="primaryTeacher === parseInt(t.id)">★ Primary Host</span>
                                                    <span x-show="primaryTeacher !== parseInt(t.id)">Set as Host</span>
                                                </button>
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                <button type="button" @click="toggleTeacher(t.id)" class="text-gray-400 hover:text-red-600 p-1 rounded transition-colors" title="Remove teacher">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 2. ASSIGN STUDENTS -->
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">
                                    Assign Students (Group Class Enrollment) <span class="text-red-500">*</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-0.5">Select multiple students who will participate in this group class session.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="selectedStudents.length > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                                    <span x-text="selectedStudents.length">0</span> Student(s) Enrolled
                                </span>
                                <button type="button" @click="activeModal = 'students'; studentSearch = ''; studentTab = 'all'; studentPage = 1"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    <span>Manage Student Directory</span>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State Box -->
                        <div x-show="selectedStudents.length === 0" 
                             @click="activeModal = 'students'; studentSearch = ''; studentTab = 'all'; studentPage = 1" 
                             class="cursor-pointer border-2 border-dashed border-gray-200 hover:border-primary/50 hover:bg-blue-50/10 rounded-xl p-5 text-center transition-all group">
                            <svg class="w-8 h-8 mx-auto text-gray-300 group-hover:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <p class="text-xs font-semibold text-gray-700 mt-2">No students enrolled yet</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Click here to search and select students from the directory.</p>
                        </div>

                        <!-- Selected Students Preview Container -->
                        <div x-show="selectedStudents.length > 0" class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-2xs">
                            <div class="px-4 py-2.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-700">Enrolled Students Cohort (<span x-text="selectedStudents.length"></span>)</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" @click="clearAllStudents()" class="text-[11px] font-semibold text-red-600 hover:underline">Clear All</button>
                                    <button type="button" @click="activeModal = 'students'; studentTab = 'selected'" class="text-[11px] font-semibold text-primary hover:underline">+ Add / Edit</button>
                                </div>
                            </div>
                            <div class="p-3" style="max-height: 220px; overflow-y: auto;">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    <template x-for="s in getSelectedStudentObjects()" :key="'sel-s-' + s.id">
                                        <div class="flex items-center justify-between p-2 rounded-lg border border-gray-200 bg-gray-50/50 hover:bg-white transition-colors">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                                    <span x-text="s.username.substring(0, 1).toUpperCase()">S</span>
                                                </div>
                                                <div class="truncate">
                                                    <p class="text-xs font-semibold text-gray-900 truncate" x-text="s.username"></p>
                                                    <p class="text-[10px] text-gray-400 truncate" x-text="s.email"></p>
                                                </div>
                                            </div>
                                            <button type="button" @click="toggleStudent(s.id)" class="text-gray-300 hover:text-red-600 ml-1 p-0.5 flex-shrink-0" title="Remove student">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ASSIGN CLASS OBSERVERS -->
                    <?php if ((function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('classrooms.assign_observer'))): ?>
                    <div class="pt-4 border-t border-gray-100 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800">Assign Class Observers (Optional)</label>
                                <p class="text-xs text-gray-500 mt-0.5">Assigned Admin or Moderator accounts will be able to observe live sessions and view logs.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="selectedObservers.length > 0 ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-600'">
                                    <span x-text="selectedObservers.length">0</span> Observer(s) Assigned
                                </span>
                                <button type="button" @click="activeModal = 'observers'; observerSearch = ''; observerTab = 'all'; observerPage = 1"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors shadow-2xs">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>Manage Observers</span>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State Box -->
                        <div x-show="selectedObservers.length === 0" 
                             @click="activeModal = 'observers'; observerSearch = ''; observerTab = 'all'; observerPage = 1" 
                             class="cursor-pointer border-2 border-dashed border-gray-200 hover:border-purple-300 hover:bg-purple-50/10 rounded-xl p-4 text-center transition-all group">
                            <p class="text-xs font-medium text-gray-500">No observers assigned (Optional). Click here to select staff or moderators.</p>
                        </div>

                        <!-- Selected Observers Badges -->
                        <div x-show="selectedObservers.length > 0" class="flex flex-wrap gap-2 p-3 bg-gray-50/50 rounded-xl border border-gray-200">
                            <template x-for="o in getSelectedObserverObjects()" :key="'sel-o-' + o.id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white border border-gray-200 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    <span class="font-bold text-gray-900" x-text="o.username"></span>
                                    <span class="text-[10px] text-gray-400 font-normal" x-text="'(' + (o.role_name || o.email) + ')'"></span>
                                    <button type="button" @click="toggleObserver(o.id)" class="text-gray-300 hover:text-red-500 ml-1">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status -->
                    <div class="pt-4 border-t border-gray-100 space-y-1.5">
                        <label for="status" class="block text-sm font-semibold text-gray-800">Status</label>
                        <div class="relative max-w-xs">
                            <select id="status" name="status" class="block w-full py-2.5 px-3 border border-gray-200 rounded-lg shadow-2xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm text-gray-900 bg-white">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Actions -->
                <div class="pt-6 border-t border-gray-100 flex flex-col sm:flex-row justify-end gap-3">
                    <a href="/admin/classrooms/index.php" class="inline-flex justify-center items-center py-2.5 px-6 border border-gray-200 shadow-2xs text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors order-2 sm:order-1">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center items-center py-2.5 px-6 border border-transparent shadow-xs text-sm font-bold rounded-lg text-white bg-primary hover:bg-emerald-700 transition-colors order-1 sm:order-2">
                        <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Save Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 1. TEACHER DIRECTORY MODAL -->
    <template x-teleport="body">
        <div x-show="activeModal === 'teachers'" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); overflow-y: auto;"
             @click.self="activeModal = null">
            
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full" 
                 style="position: relative; max-width: 720px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; margin: auto;"
                 @click.stop>
                
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/70" style="flex-shrink: 0;">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Select Teachers Directory</h3>
                            <p class="text-xs text-gray-500">Pick one or more instructors for this classroom</p>
                        </div>
                    </div>
                    <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-3.5 sm:p-4 border-b border-gray-100 bg-white space-y-2.5" style="flex-shrink: 0;">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" x-model="teacherSearch" @input="teacherPage = 1" placeholder="Search teacher by name or email..." class="block w-full pl-9 pr-8 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            <button type="button" x-show="teacherSearch.length > 0" @click="teacherSearch = ''; teacherPage = 1" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg text-xs font-semibold">
                            <button type="button" @click="teacherTab = 'all'; teacherPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="teacherTab === 'all' ? 'bg-white text-gray-900 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                All (<span x-text="teachersList.length"></span>)
                            </button>
                            <button type="button" @click="teacherTab = 'selected'; teacherPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="teacherTab === 'selected' ? 'bg-white text-emerald-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                Selected (<span x-text="selectedTeachers.length"></span>)
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllFilteredTeachers()" class="text-xs font-bold text-primary hover:underline">Select All Filtered</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="clearAllTeachers()" class="text-xs font-semibold text-gray-500 hover:underline">Clear Selection</button>
                        </div>
                        <span class="text-gray-400" x-text="'Showing ' + paginatedTeachers().length + ' of ' + filteredTeachers().length + ' matching'"></span>
                    </div>
                </div>

                <div style="flex: 1 1 auto; min-height: 0; max-height: calc(85vh - 220px); overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; background: #ffffff;">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/90" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-12 text-center">Select</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Teacher</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Email Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs bg-white">
                            <template x-for="t in paginatedTeachers()" :key="'dlg-t-' + t.id">
                                <tr @click="toggleTeacher(t.id)" 
                                    class="cursor-pointer transition-colors"
                                    :class="selectedTeachers.includes(parseInt(t.id)) ? 'bg-emerald-50/40 hover:bg-emerald-50/60' : 'hover:bg-gray-50/80'">
                                    <td class="px-4 py-2.5 text-center" @click.stop>
                                        <input type="checkbox" :checked="selectedTeachers.includes(parseInt(t.id))" @change="toggleTeacher(t.id)" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                <span x-text="t.username.substring(0, 1).toUpperCase()">T</span>
                                            </div>
                                            <span class="font-bold text-gray-900" x-text="t.username"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500" x-text="t.email"></td>
                                </tr>
                            </template>

                            <tr x-show="filteredTeachers().length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-xs text-gray-400">
                                    No teachers found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="totalTeacherPages() > 1" class="px-4 py-2 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between text-xs text-gray-600" style="flex-shrink: 0;">
                    <span x-text="'Page ' + teacherPage + ' of ' + totalTeacherPages()"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="teacherPage = Math.max(1, teacherPage - 1)" :disabled="teacherPage === 1" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Prev</button>
                        <button type="button" @click="teacherPage = Math.min(totalTeacherPages(), teacherPage + 1)" :disabled="teacherPage === totalTeacherPages()" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Next</button>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 bg-white flex items-center justify-between" style="flex-shrink: 0;">
                    <div class="text-xs font-semibold text-gray-700">
                        <span class="text-primary font-bold" x-text="selectedTeachers.length"></span> teacher(s) selected
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="activeModal = null" class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                            Done / Apply Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 2. STUDENT DIRECTORY MODAL -->
    <template x-teleport="body">
        <div x-show="activeModal === 'students'" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); overflow-y: auto;"
             @click.self="activeModal = null">
            
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full" 
                 style="position: relative; max-width: 720px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; margin: auto;"
                 @click.stop>
                
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/70" style="flex-shrink: 0;">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Student Directory (Group Enrollment)</h3>
                            <p class="text-xs text-gray-500">Search and select students to enroll in this group class</p>
                        </div>
                    </div>
                    <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-3.5 sm:p-4 border-b border-gray-100 bg-white space-y-2.5" style="flex-shrink: 0;">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" x-model="studentSearch" @input="studentPage = 1" placeholder="Search student by name or email..." class="block w-full pl-9 pr-8 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            <button type="button" x-show="studentSearch.length > 0" @click="studentSearch = ''; studentPage = 1" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg text-xs font-semibold">
                            <button type="button" @click="studentTab = 'all'; studentPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="studentTab === 'all' ? 'bg-white text-gray-900 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                All (<span x-text="studentsList.length"></span>)
                            </button>
                            <button type="button" @click="studentTab = 'selected'; studentPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="studentTab === 'selected' ? 'bg-white text-blue-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                Selected (<span x-text="selectedStudents.length"></span>)
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllFilteredStudents()" class="text-xs font-bold text-primary hover:underline">Select All Filtered</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="clearAllStudents()" class="text-xs font-semibold text-gray-500 hover:underline">Clear Selection</button>
                        </div>
                        <span class="text-gray-400" x-text="'Showing ' + paginatedStudents().length + ' of ' + filteredStudents().length + ' matching'"></span>
                    </div>
                </div>

                <div style="flex: 1 1 auto; min-height: 0; max-height: calc(85vh - 220px); overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; background: #ffffff;">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/90" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-12 text-center">Enroll</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Email Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs bg-white">
                            <template x-for="s in paginatedStudents()" :key="'dlg-s-' + s.id">
                                <tr @click="toggleStudent(s.id)" 
                                    class="cursor-pointer transition-colors"
                                    :class="selectedStudents.includes(parseInt(s.id)) ? 'bg-blue-50/40 hover:bg-blue-50/60' : 'hover:bg-gray-50/80'">
                                    <td class="px-4 py-2.5 text-center" @click.stop>
                                        <input type="checkbox" :checked="selectedStudents.includes(parseInt(s.id))" @change="toggleStudent(s.id)" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                <span x-text="s.username.substring(0, 1).toUpperCase()">S</span>
                                            </div>
                                            <span class="font-bold text-gray-900" x-text="s.username"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500" x-text="s.email"></td>
                                </tr>
                            </template>

                            <tr x-show="filteredStudents().length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-xs text-gray-400">
                                    No students found matching your search.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="totalStudentPages() > 1" class="px-4 py-2 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between text-xs text-gray-600" style="flex-shrink: 0;">
                    <span x-text="'Page ' + studentPage + ' of ' + totalStudentPages()"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="studentPage = Math.max(1, studentPage - 1)" :disabled="studentPage === 1" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Prev</button>
                        <button type="button" @click="studentPage = Math.min(totalStudentPages(), studentPage + 1)" :disabled="studentPage === totalStudentPages()" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Next</button>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 bg-white flex items-center justify-between" style="flex-shrink: 0;">
                    <div class="text-xs font-semibold text-gray-700">
                        <span class="text-blue-700 font-bold" x-text="selectedStudents.length"></span> student(s) selected for enrollment
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="activeModal = null" class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                            Confirm Enrollment (<span x-text="selectedStudents.length"></span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- 3. OBSERVER DIRECTORY MODAL -->
    <?php if ((function_exists('isSuperAdmin') && isSuperAdmin()) || (function_exists('hasPermission') && hasPermission('classrooms.assign_observer'))): ?>
    <template x-teleport="body">
        <div x-show="activeModal === 'observers'" 
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 1rem; background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); overflow-y: auto;"
             @click.self="activeModal = null">
            
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 w-full" 
                 style="position: relative; max-width: 720px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; margin: auto;"
                 @click.stop>
                
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/70" style="flex-shrink: 0;">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900">Select Observers</h3>
                            <p class="text-xs text-gray-500">Assign staff, admin, or moderators to monitor this class</p>
                        </div>
                    </div>
                    <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-3.5 sm:p-4 border-b border-gray-100 bg-white space-y-2.5" style="flex-shrink: 0;">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" x-model="observerSearch" @input="observerPage = 1" placeholder="Search observer by name or email..." class="block w-full pl-9 pr-8 py-2 border border-gray-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                            <button type="button" x-show="observerSearch.length > 0" @click="observerSearch = ''; observerPage = 1" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg text-xs font-semibold">
                            <button type="button" @click="observerTab = 'all'; observerPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="observerTab === 'all' ? 'bg-white text-gray-900 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                All (<span x-text="observersList.length"></span>)
                            </button>
                            <button type="button" @click="observerTab = 'selected'; observerPage = 1" 
                                    class="px-3 py-1 rounded-md transition-all"
                                    :class="observerTab === 'selected' ? 'bg-white text-purple-700 shadow-2xs' : 'text-gray-600 hover:text-gray-900'">
                                Selected (<span x-text="selectedObservers.length"></span>)
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllFilteredObservers()" class="text-xs font-bold text-primary hover:underline">Select All Filtered</button>
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="clearAllObservers()" class="text-xs font-semibold text-gray-500 hover:underline">Clear Selection</button>
                        </div>
                        <span class="text-gray-400" x-text="'Showing ' + paginatedObservers().length + ' of ' + filteredObservers().length + ' matching'"></span>
                    </div>
                </div>

                <div style="flex: 1 1 auto; min-height: 0; max-height: calc(85vh - 220px); overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; background: #ffffff;">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/90" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider w-12 text-center">Select</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Email Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-xs bg-white">
                            <template x-for="o in paginatedObservers()" :key="'dlg-o-' + o.id">
                                <tr @click="toggleObserver(o.id)" 
                                    class="cursor-pointer transition-colors"
                                    :class="selectedObservers.includes(parseInt(o.id)) ? 'bg-purple-50/40 hover:bg-purple-50/60' : 'hover:bg-gray-50/80'">
                                    <td class="px-4 py-2.5 text-center" @click.stop>
                                        <input type="checkbox" :checked="selectedObservers.includes(parseInt(o.id))" @change="toggleObserver(o.id)" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2.5 font-bold text-gray-900" x-text="o.username"></td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700" x-text="o.role_name || 'Staff'"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500" x-text="o.email"></td>
                                </tr>
                            </template>

                            <tr x-show="filteredObservers().length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-xs text-gray-400">
                                    No eligible observer accounts found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div x-show="totalObserverPages() > 1" class="px-4 py-2 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between text-xs text-gray-600" style="flex-shrink: 0;">
                    <span x-text="'Page ' + observerPage + ' of ' + totalObserverPages()"></span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="observerPage = Math.max(1, observerPage - 1)" :disabled="observerPage === 1" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Prev</button>
                        <button type="button" @click="observerPage = Math.min(totalObserverPages(), observerPage + 1)" :disabled="observerPage === totalObserverPages()" class="px-2.5 py-1 border border-gray-200 rounded bg-white disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50">Next</button>
                    </div>
                </div>

                <div class="px-5 py-3 border-t border-gray-100 bg-white flex items-center justify-between" style="flex-shrink: 0;">
                    <div class="text-xs font-semibold text-gray-700">
                        <span class="text-purple-700 font-bold" x-text="selectedObservers.length"></span> observer(s) assigned
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="activeModal = null" class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="activeModal = null" class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-primary hover:bg-emerald-700 transition-colors shadow-2xs">
                            Done / Apply Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <?php endif; ?>

</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
