<?php
$pageTitle = 'Edit Notice';
$activeMenu = 'notices_manage';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar_admin.php';

$title       = $notice['title']       ?? '';
$content     = $notice['content']     ?? '';
$status      = $notice['status']      ?? 'active';
$targetType  = $notice['target_type'] ?? 'targeted';

$audienceStudent = !empty($notice['audience_student']);
$audienceTeacher = !empty($notice['audience_teacher']);

$selectedClassrooms = $notice['selected_classrooms'] ?? [];
$selectedUsers      = $notice['selected_users']      ?? [];

$classroomsJson = json_encode(array_values($classrooms ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';
$usersJson      = json_encode(array_values($users ?? []), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: '[]';
?>

<script nonce="<?php echo csp_nonce(); ?>">
function noticeTargetManager() {
    return {
        targetType: '<?php echo e($targetType); ?>', // 'all' | 'targeted'
        targetTab: 'roles', // 'roles' | 'classrooms' | 'users'

        // Role States
        audienceStudent: <?php echo $audienceStudent ? 'true' : 'false'; ?>,
        audienceTeacher: <?php echo $audienceTeacher ? 'true' : 'false'; ?>,

        // Classroom States
        classroomsList: <?php echo $classroomsJson; ?>,
        selectedClassrooms: <?php echo json_encode(array_values($selectedClassrooms)); ?>,
        classroomSearch: '',

        // User States
        usersList: <?php echo $usersJson; ?>,
        selectedUsers: <?php echo json_encode(array_values($selectedUsers)); ?>,
        userSearch: '',
        userRoleFilter: 'all',
        userPage: 1,
        userPerPage: 8,
        isSearchingUsers: false,
        searchDebounceTimer: null,

        async fetchUsers() {
            this.isSearchingUsers = true;
            try {
                const params = new URLSearchParams();
                if (this.userSearch.trim()) params.append('q', this.userSearch.trim());
                if (this.userRoleFilter && this.userRoleFilter !== 'all') params.append('role', this.userRoleFilter.toLowerCase());
                params.append('limit', '30');

                const response = await fetch('/admin/api/users/search.php?' + params.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const result = await response.json();
                    const newUsers = result.data || result || [];
                    const selectedMap = {};
                    this.usersList.forEach(u => {
                        if (this.selectedUsers.includes(parseInt(u.id))) {
                            selectedMap[u.id] = u;
                        }
                    });
                    newUsers.forEach(u => {
                        selectedMap[u.id] = u;
                    });
                    this.usersList = Object.values(selectedMap);
                    this.userPage = 1;
                }
            } catch (err) {
                console.error('Error fetching users:', err);
            } finally {
                this.isSearchingUsers = false;
            }
        },
        onUserSearchInput() {
            this.userPage = 1;
            clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                this.fetchUsers();
            }, 300);
        },
        onUserRoleFilterChange(role) {
            this.userRoleFilter = role;
            this.userPage = 1;
            this.fetchUsers();
        },

        // Classroom Methods
        filteredClassrooms() {
            if (!this.classroomSearch.trim()) return this.classroomsList;
            const q = this.classroomSearch.toLowerCase();
            return this.classroomsList.filter(c =>
                (c.class_name && c.class_name.toLowerCase().includes(q)) ||
                (c.class_code && c.class_code.toLowerCase().includes(q))
            );
        },
        toggleClassroom(id) {
            id = parseInt(id);
            const idx = this.selectedClassrooms.indexOf(id);
            if (idx > -1) {
                this.selectedClassrooms.splice(idx, 1);
            } else {
                this.selectedClassrooms.push(id);
            }
        },
        selectAllClassrooms() {
            this.selectedClassrooms = this.classroomsList.map(c => parseInt(c.id));
        },
        clearClassrooms() {
            this.selectedClassrooms = [];
        },

        // User Methods
        filteredUsers() {
            let list = this.usersList;
            if (this.userRoleFilter !== 'all') {
                list = list.filter(u => u.role_name === this.userRoleFilter);
            }
            if (this.userSearch.trim()) {
                const q = this.userSearch.toLowerCase();
                list = list.filter(u =>
                    (u.username && u.username.toLowerCase().includes(q)) ||
                    (u.email && u.email.toLowerCase().includes(q))
                );
            }
            return list;
        },
        paginatedUsers() {
            const list = this.filteredUsers();
            const start = (this.userPage - 1) * this.userPerPage;
            return list.slice(start, start + this.userPerPage);
        },
        totalUserPages() {
            return Math.ceil(this.filteredUsers().length / this.userPerPage) || 1;
        },
        toggleUser(id) {
            id = parseInt(id);
            const idx = this.selectedUsers.indexOf(id);
            if (idx > -1) {
                this.selectedUsers.splice(idx, 1);
            } else {
                this.selectedUsers.push(id);
            }
        },
        selectAllFilteredUsers() {
            const filtered = this.filteredUsers();
            filtered.forEach(u => {
                const id = parseInt(u.id);
                if (!this.selectedUsers.includes(id)) {
                    this.selectedUsers.push(id);
                }
            });
        },
        clearUsers() {
            this.selectedUsers = [];
        },
        getSelectedUserObjects() {
            return this.usersList.filter(u => this.selectedUsers.includes(parseInt(u.id)));
        },
        getSelectedClassroomObjects() {
            return this.classroomsList.filter(c => this.selectedClassrooms.includes(parseInt(c.id)));
        },

        // Validation before submit
        validateAndSubmit(e) {
            if (this.targetType === 'targeted') {
                if (!this.audienceStudent && !this.audienceTeacher && this.selectedClassrooms.length === 0 && this.selectedUsers.length === 0) {
                    e.preventDefault();
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Target Required',
                            text: 'Please select at least one Role, Classroom, or Specific User for targeted announcement.',
                            confirmButtonColor: '#059669'
                        });
                    } else {
                        alert('Please select at least one Role, Classroom, or Specific User for targeted announcement.');
                    }
                    return false;
                }
            }
            return true;
        }
    };
}
</script>

<main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-[#f8fafc]" x-data="noticeTargetManager()">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Edit Notice</h1>
                <p class="mt-1 text-sm text-gray-500">Update announcement details, status, or audience targeting.</p>
            </div>
            <a href="index.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Notices
            </a>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative flex items-center gap-3" role="alert">
                <i class="ph ph-warning-circle text-xl text-red-600"></i>
                <span class="block sm:inline text-sm font-medium"><?php echo e($errorMessage); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-xs border border-gray-100 rounded-2xl p-6 sm:p-8">
            <form action="edit.php?id=<?php echo (int)($notice['id'] ?? 0); ?>" method="POST" enctype="multipart/form-data" class="space-y-8" @submit="validateAndSubmit($event)">
                <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
                <input type="hidden" name="id" value="<?php echo (int)($notice['id'] ?? 0); ?>">
                
                <!-- Hidden inputs for selected classrooms and users -->
                <template x-for="cid in selectedClassrooms" :key="'cr_' + cid">
                    <input type="hidden" name="target_classroom_ids[]" :value="cid">
                </template>
                <template x-for="uid in selectedUsers" :key="'usr_' + uid">
                    <input type="hidden" name="target_user_ids[]" :value="uid">
                </template>

                <!-- Basic Fields -->
                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-gray-800">Notice Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required value="<?php echo e($title); ?>" placeholder="Enter notice title" class="block w-full border border-gray-200 rounded-xl shadow-2xs py-2.5 px-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary sm:text-sm transition-colors font-medium">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-sm font-semibold text-gray-800">Notice Description <span class="text-red-500">*</span></label>
                    <textarea name="content" required rows="5" placeholder="Write full notice description here..." class="block w-full border border-gray-200 rounded-xl shadow-2xs py-2.5 px-3.5 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary sm:text-sm transition-colors"><?php echo e($content); ?></textarea>
                </div>

                <!-- Target Distribution Section -->
                <div class="space-y-4 border-t border-gray-100 pt-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900">Target Audience & Distribution</label>
                        <p class="text-xs text-gray-500 mt-0.5">Select whether this notice is a public broadcast or targeted to specific roles, groups, or users.</p>
                    </div>

                    <!-- Target Type Selector Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-150"
                               :class="targetType === 'all' ? 'border-primary bg-emerald-50/30 ring-1 ring-primary' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="all" x-model="targetType" class="mt-0.5 h-4 w-4 text-primary focus:ring-primary border-gray-300">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                    <i class="ph ph-broadcast text-primary text-base"></i>
                                    Broadcast to All
                                </span>
                                <span class="block text-xs text-gray-500 mt-1 leading-normal">
                                    Public announcement visible to all students, teachers, and staff members across the portal.
                                </span>
                            </div>
                        </label>

                        <label class="relative flex items-start p-4 rounded-xl border cursor-pointer transition-all duration-150"
                               :class="targetType === 'targeted' ? 'border-primary bg-emerald-50/30 ring-1 ring-primary' : 'border-gray-200 hover:border-gray-300 bg-white'">
                            <input type="radio" name="target_type" value="targeted" x-model="targetType" class="mt-0.5 h-4 w-4 text-primary focus:ring-primary border-gray-300">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900 flex items-center gap-1.5">
                                    <i class="ph ph-crosshair text-primary text-base"></i>
                                    Targeted Announcement
                                </span>
                                <span class="block text-xs text-gray-500 mt-1 leading-normal">
                                    Restrict visibility to specific Roles, Group Classrooms, or Individual Users.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Targeted Options Container -->
                    <div x-show="targetType === 'targeted'" x-cloak class="bg-gray-50/70 border border-gray-200/80 rounded-2xl p-5 space-y-5 transition-all">
                        <!-- Navigation Sub-tabs -->
                        <div class="flex items-center gap-2 border-b border-gray-200 pb-3">
                            <button type="button" @click="targetTab = 'roles'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
                                    :class="targetTab === 'roles' ? 'bg-white text-gray-900 shadow-2xs border border-gray-200/80' : 'text-gray-500 hover:text-gray-800'">
                                <i class="ph ph-users text-sm"></i>
                                By Roles
                                <span x-show="audienceStudent || audienceTeacher" class="w-2 h-2 rounded-full bg-primary inline-block"></span>
                            </button>
                            <button type="button" @click="targetTab = 'classrooms'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
                                    :class="targetTab === 'classrooms' ? 'bg-white text-gray-900 shadow-2xs border border-gray-200/80' : 'text-gray-500 hover:text-gray-800'">
                                <i class="ph ph-chalkboard text-sm"></i>
                                Classrooms / Groups
                                <span x-show="selectedClassrooms.length > 0" class="ml-1 px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] rounded-full font-bold" x-text="selectedClassrooms.length"></span>
                            </button>
                            <button type="button" @click="targetTab = 'users'"
                                    class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5"
                                    :class="targetTab === 'users' ? 'bg-white text-gray-900 shadow-2xs border border-gray-200/80' : 'text-gray-500 hover:text-gray-800'">
                                <i class="ph ph-user text-sm"></i>
                                Specific Users
                                <span x-show="selectedUsers.length > 0" class="ml-1 px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[10px] rounded-full font-bold" x-text="selectedUsers.length"></span>
                            </button>
                        </div>

                        <!-- TAB 1: Roles Selection -->
                        <div x-show="targetTab === 'roles'" class="space-y-3">
                            <p class="text-xs text-gray-500">Select which general user roles should receive this notice.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-primary/40 cursor-pointer transition-colors">
                                    <input type="checkbox" name="audience_student" value="1" x-model="audienceStudent" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer">
                                    <div>
                                        <div class="text-xs font-bold text-gray-800">All Students</div>
                                        <div class="text-[11px] text-gray-400">Published to every student's dashboard</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-200 hover:border-primary/40 cursor-pointer transition-colors">
                                    <input type="checkbox" name="audience_teacher" value="1" x-model="audienceTeacher" class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer">
                                    <div>
                                        <div class="text-xs font-bold text-gray-800">All Teachers</div>
                                        <div class="text-[11px] text-gray-400">Published to every teacher's dashboard</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- TAB 2: Classrooms Multi-select -->
                        <div x-show="targetTab === 'classrooms'" class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div class="relative flex-1 max-w-sm">
                                    <input type="text" x-model="classroomSearch" placeholder="Search classrooms..." class="w-full text-xs py-2 pl-8 pr-3 border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                                    <i class="ph ph-magnifying-glass absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="selectAllClassrooms()" class="text-primary font-bold hover:underline">Select All</button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" @click="clearClassrooms()" class="text-gray-500 font-semibold hover:underline">Clear</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 max-h-56 overflow-y-auto pr-1">
                                <template x-for="c in filteredClassrooms()" :key="c.id">
                                    <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-all"
                                           :class="selectedClassrooms.includes(parseInt(c.id)) ? 'bg-emerald-50/40 border-primary' : 'bg-white border-gray-200 hover:border-gray-300'">
                                        <input type="checkbox" :checked="selectedClassrooms.includes(parseInt(c.id))" @change="toggleClassroom(c.id)" class="mt-0.5 h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-gray-900 truncate" x-text="c.class_name"></div>
                                            <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-0.5">
                                                <span class="font-mono text-gray-400" x-text="c.class_code"></span>
                                                <span>•</span>
                                                <span x-text="(c.student_count || 0) + ' Students'"></span>
                                            </div>
                                        </div>
                                    </label>
                                </template>
                                <div x-show="filteredClassrooms().length === 0" class="col-span-2 py-4 text-center text-xs text-gray-400">
                                    No classrooms found matching search.
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Specific Users Searchable Multi-select -->
                        <div x-show="targetTab === 'users'" class="space-y-3">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="relative flex-1 max-w-sm">
                                        <input type="text" x-model="userSearch" @input="onUserSearchInput()" placeholder="Search user name or email..." class="w-full text-xs py-2 pl-8 pr-8 border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-primary">
                                        <i class="ph ph-magnifying-glass absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                                        <i x-show="isSearchingUsers" class="ph ph-spinner animate-spin absolute right-2.5 top-2.5 text-primary text-xs" style="display: none;"></i>
                                    </div>
                                    <div class="flex items-center bg-gray-100 p-0.5 rounded-lg text-[11px] font-semibold text-gray-600">
                                        <button type="button" @click="onUserRoleFilterChange('all')" class="px-2 py-1 rounded-md" :class="userRoleFilter === 'all' ? 'bg-white text-gray-900 shadow-2xs' : ''">All</button>
                                        <button type="button" @click="onUserRoleFilterChange('Student')" class="px-2 py-1 rounded-md" :class="userRoleFilter === 'Student' ? 'bg-white text-gray-900 shadow-2xs' : ''">Students</button>
                                        <button type="button" @click="onUserRoleFilterChange('Teacher')" class="px-2 py-1 rounded-md" :class="userRoleFilter === 'Teacher' ? 'bg-white text-gray-900 shadow-2xs' : ''">Teachers</button>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between sm:justify-end gap-2 text-xs">
                                    <button type="button" @click="selectAllFilteredUsers()" class="text-primary font-bold hover:underline">Select Filtered</button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" @click="clearUsers()" class="text-gray-500 font-semibold hover:underline">Clear</button>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                                <div class="divide-y divide-gray-100 max-h-56 overflow-y-auto">
                                    <template x-for="u in paginatedUsers()" :key="u.id">
                                        <div @click="toggleUser(u.id)" class="flex items-center justify-between p-2.5 px-3.5 hover:bg-gray-50/80 cursor-pointer transition-colors"
                                             :class="selectedUsers.includes(parseInt(u.id)) ? 'bg-emerald-50/30' : ''">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <input type="checkbox" :checked="selectedUsers.includes(parseInt(u.id))" @change.stop="toggleUser(u.id)" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-gray-900 truncate" x-text="u.username"></div>
                                                    <div class="text-[11px] text-gray-400 truncate" x-text="u.email"></div>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize"
                                                  :class="u.role_name === 'Teacher' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                                  x-text="u.role_name"></span>
                                        </div>
                                    </template>
                                    <div x-show="filteredUsers().length === 0" class="py-6 text-center text-xs text-gray-400">
                                        No users matching current filters.
                                    </div>
                                </div>

                                <div class="p-2.5 px-4 bg-gray-50/90 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                    <span x-text="'Showing ' + paginatedUsers().length + ' of ' + filteredUsers().length + ' users'"></span>
                                    <div class="flex items-center gap-1.5" x-show="totalUserPages() > 1">
                                        <button type="button" @click="userPage = Math.max(1, userPage - 1)" :disabled="userPage === 1" class="px-2 py-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">Prev</button>
                                        <span class="text-[11px] font-medium" x-text="'Page ' + userPage + ' / ' + totalUserPages()"></span>
                                        <button type="button" @click="userPage = Math.min(totalUserPages(), userPage + 1)" :disabled="userPage >= totalUserPages()" class="px-2 py-1 rounded bg-white border border-gray-200 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Summary Chips -->
                        <div class="pt-3 border-t border-gray-200/80 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-gray-500 font-semibold">Active Targets:</span>
                            <span x-show="audienceStudent" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 text-[11px] font-medium">Students</span>
                            <span x-show="audienceTeacher" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[11px] font-medium">Teachers</span>
                            <span x-show="selectedClassrooms.length > 0" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-medium" x-text="selectedClassrooms.length + ' Classroom(s)'"></span>
                            <span x-show="selectedUsers.length > 0" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-medium" x-text="selectedUsers.length + ' User(s)'"></span>
                            <span x-show="!audienceStudent && !audienceTeacher && selectedClassrooms.length === 0 && selectedUsers.length === 0" class="text-red-500 font-semibold text-[11px]">⚠️ None selected yet</span>
                        </div>
                    </div>
                </div>

                <!-- Status Selection -->
                <div class="border-t border-gray-100 pt-6">
                    <label class="block text-sm font-semibold text-gray-800 mb-2">Publishing Status</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="status" value="active" <?php echo $status === 'active' ? 'checked' : ''; ?> class="h-4 w-4 border-gray-300 text-primary focus:ring-primary cursor-pointer">
                            <span class="ml-2.5 text-sm text-gray-700 font-medium">Active (Visible immediately)</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="status" value="inactive" <?php echo $status === 'inactive' ? 'checked' : ''; ?> class="h-4 w-4 border-gray-300 text-primary focus:ring-primary cursor-pointer">
                            <span class="ml-2.5 text-sm text-gray-700 font-medium">Draft / Inactive</span>
                        </label>
                    </div>
                </div>

                <!-- Attachments Dropzone -->
                <div class="space-y-2 border-t border-gray-100 pt-6">
                    <label class="block text-sm font-semibold text-gray-800">Add Attachments (Optional)</label>
                    <p class="text-xs text-gray-500 mb-3">Allowed types: .pdf, .doc, .docx, .txt, .jpg, .png. Maximum 10MB per file.</p>
                    
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file-edit" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-200 border-dashed rounded-2xl cursor-pointer bg-gray-50/60 hover:bg-gray-100/70 hover:border-primary transition-all duration-200">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-2.5 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="mb-1 text-sm text-gray-600"><span class="font-semibold text-primary">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-400">Multiple files supported</p>
                            </div>
                            <input id="dropzone-file-edit" name="attachments[]" type="file" multiple accept=".txt,.pdf,.jpg,.jpeg,.png,.doc,.docx" class="hidden" onchange="document.getElementById('file-count-edit').textContent = this.files.length > 0 ? this.files.length + ' file(s) selected' : '';" />
                        </label>
                    </div>
                    <div id="file-count-edit" class="text-sm text-primary mt-2 font-medium"></div>

                    <?php if (!empty($notice['attachments'])): ?>
                        <div class="mt-4">
                            <h4 class="text-xs font-semibold text-gray-700 mb-2">Existing Attachments</h4>
                            <ul class="space-y-1.5">
                                <?php foreach ($notice['attachments'] as $attachment): ?>
                                    <li class="flex items-center text-xs text-gray-600 bg-gray-50/80 p-2.5 rounded-xl border border-gray-100">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                        <span class="font-medium truncate"><?php echo e($attachment['file_name']); ?></span>
                                        <span class="ml-auto text-[11px] text-gray-400">(<?php echo round($attachment['file_size'] / 1024, 2); ?> KB)</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Submit Buttons -->
                <div class="flex flex-col sm:flex-row justify-end pt-6 border-t border-gray-100 gap-3">
                    <a href="index.php" class="inline-flex justify-center items-center py-2.5 px-5 border border-gray-300 shadow-2xs text-sm font-semibold rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition-colors order-2 sm:order-1">Cancel</a>
                    <button type="submit" class="inline-flex justify-center items-center py-2.5 px-6 border border-transparent shadow-xs text-sm font-semibold rounded-xl text-white bg-primary hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors order-1 sm:order-2">Update Notice</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../layouts/footer.php'; ?>
