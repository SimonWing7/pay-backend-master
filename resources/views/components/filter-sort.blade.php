@props(['route', 'filters' => [], 'sortBy' => 'created_at', 'sortDir' => 'desc'])

<div class="bg-gradient-to-br from-gray-50 to-white shadow-lg rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center mb-4">
        <i class="fas fa-filter text-blue-500 mr-2 text-xl"></i>
        <h3 class="text-lg font-semibold text-gray-800">Filters & Sorting</h3>
    </div>
    <form method="GET" action="{{ $route }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @if(isset($filters['search']))
            <div class="flex flex-col">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-search text-gray-400 mr-1.5 text-xs"></i>
                    Search
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search..."
                        class="pl-10 pr-3 py-2.5 h-10 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg transition duration-150 ease-in-out bg-white">
                </div>
            </div>
            @endif

            @if(isset($filters['status']))
            <div class="flex flex-col">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-info-circle text-gray-400 mr-1.5 text-xs"></i>
                    Status
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </div>
                    <select name="status" id="status" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                        <option value="">All Statuses</option>
                        @foreach($filters['status_options'] ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            @if(isset($filters['date_from']))
            <div class="flex flex-col">
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-calendar-alt text-gray-400 mr-1.5 text-xs"></i>
                    From Date
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar text-gray-400 text-sm"></i>
                    </div>
                    <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                        class="pl-10 pr-3 py-2.5 h-10 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg transition duration-150 ease-in-out bg-white">
                </div>
            </div>
            @endif

            @if(isset($filters['date_to']))
            <div class="flex flex-col">
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-calendar-check text-gray-400 mr-1.5 text-xs"></i>
                    To Date
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar text-gray-400 text-sm"></i>
                    </div>
                    <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                        class="pl-10 pr-3 py-2.5 h-10 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg transition duration-150 ease-in-out bg-white">
                </div>
            </div>
            @endif

            @if(isset($filters['is_active']))
            <div class="flex flex-col">
                <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-toggle-on text-gray-400 mr-1.5 text-xs"></i>
                    Active Status
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </div>
                    <select name="is_active" id="is_active" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            @endif

            @if(isset($filters['consumer_id']))
            <div class="flex flex-col">
                <label for="consumer_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-user-tag text-gray-400 mr-1.5 text-xs"></i>
                    Consumer
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </div>
                    <select name="consumer_id" id="consumer_id" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                        <option value="">All Consumers</option>
                        @foreach($filters['consumers'] ?? [] as $consumer)
                        <option value="{{ $consumer->id }}" {{ request('consumer_id') == $consumer->id ? 'selected' : '' }}>{{ $consumer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            @if(isset($filters['group_id']))
            <div class="flex flex-col">
                <label for="group_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-layer-group text-gray-400 mr-1.5 text-xs"></i>
                    Group
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                    </div>
                    <select name="group_id" id="group_id" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                        <option value="">All Groups</option>
                        @foreach($filters['groups'] ?? [] as $group)
                        <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                            <span style="display: inline-block; width: 12px; height: 12px; background-color: {{ $group->color }}; border-radius: 50%; margin-right: 5px;"></span>
                            {{ $group->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            @if(isset($filters['min_fee']))
            <div class="flex flex-col">
                <label for="min_fee" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-dollar-sign text-gray-400 mr-1.5 text-xs"></i>
                    Min Fee
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-sm">AED</span>
                    </div>
                    <input type="number" name="min_fee" id="min_fee" value="{{ request('min_fee') }}" step="0.01" min="0"
                        class="pl-14 pr-3 py-2.5 h-10 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg transition duration-150 ease-in-out bg-white">
                </div>
            </div>
            @endif

            @if(isset($filters['max_fee']))
            <div class="flex flex-col">
                <label for="max_fee" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                    <i class="fas fa-dollar-sign text-gray-400 mr-1.5 text-xs"></i>
                    Max Fee
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-sm">AED</span>
                    </div>
                    <input type="number" name="max_fee" id="max_fee" value="{{ request('max_fee') }}" step="0.01" min="0"
                        class="pl-14 pr-3 py-2.5 h-10 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block w-full text-sm border-gray-300 rounded-lg transition duration-150 ease-in-out bg-white">
                </div>
            </div>
            @endif
        </div>

        <div class="border-t border-gray-200 pt-4 mt-4">
            <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-4">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="flex flex-col">
                        <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-sort text-gray-400 mr-1.5 text-xs"></i>
                            Sort By
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </div>
                            <select name="sort_by" id="sort_by" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                                @foreach($filters['sort_options'] ?? ['created_at' => 'Created At', 'updated_at' => 'Updated At'] as $value => $label)
                                <option value="{{ $value }}" {{ request('sort_by', $sortBy) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label for="sort_dir" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-arrows-alt-v text-gray-400 mr-1.5 text-xs"></i>
                            Direction
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </div>
                            <select name="sort_dir" id="sort_dir" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                                <option value="asc" {{ request('sort_dir', $sortDir) == 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ request('sort_dir', $sortDir) == 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label for="per_page" class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-list-ol text-gray-400 mr-1.5 text-xs"></i>
                            Per Page
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </div>
                            <select name="per_page" id="per_page" class="appearance-none shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 block text-sm border-gray-300 rounded-lg pl-3 pr-10 py-2.5 h-10 bg-white transition duration-150 ease-in-out">
                                @foreach([10, 15, 25, 50, 100] as $perPage)
                                <option value="{{ $perPage }}" {{ request('per_page', 15) == $perPage ? 'selected' : '' }}>{{ $perPage }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-md transition duration-150 ease-in-out transform hover:scale-105 flex items-center h-10">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="{{ $route }}" class="bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold py-2.5 px-6 rounded-lg shadow-md transition duration-150 ease-in-out transform hover:scale-105 flex items-center h-10">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
