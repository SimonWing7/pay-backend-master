@props(['route', 'filters' => [], 'sortBy' => 'created_at', 'sortDir' => 'desc'])

<div class="card p-4 mb-4">
    <form method="GET" action="{{ $route }}" class="flex flex-wrap items-center gap-3">

        @if(isset($filters['search']))
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search…"
            class="form-input text-sm py-2 flex-1 min-w-48">
        @endif

        @if(isset($filters['status']))
        <select name="status" class="form-input text-sm py-2 w-40">
            <option value="">All Statuses</option>
            @foreach($filters['status_options'] ?? [] as $value => $label)
            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @endif

        @if(isset($filters['is_active']))
        <select name="is_active" class="form-input text-sm py-2 w-36">
            <option value="">All</option>
            <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
        @endif

        @if(isset($filters['consumer_id']))
        <select name="consumer_id" class="form-input text-sm py-2 w-44">
            <option value="">All Individuals</option>
            @foreach($filters['consumers'] ?? [] as $consumer)
            <option value="{{ $consumer->id }}" {{ request('consumer_id') == $consumer->id ? 'selected' : '' }}>{{ $consumer->name }}</option>
            @endforeach
        </select>
        @endif

        @if(isset($filters['group_id']))
        <select name="group_id" class="form-input text-sm py-2 w-40">
            <option value="">All Groups</option>
            @foreach($filters['groups'] ?? [] as $group)
            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
        @endif

        @if(isset($filters['date_from']))
        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="form-input text-sm py-2 w-36" title="From date">
        @endif

        @if(isset($filters['date_to']))
        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="form-input text-sm py-2 w-36" title="To date">
        @endif

        <select name="sort_by" class="form-input text-sm py-2 w-36">
            @foreach($filters['sort_options'] ?? ['created_at' => 'Date Created', 'updated_at' => 'Date Updated'] as $value => $label)
            <option value="{{ $value }}" {{ request('sort_by', $sortBy) == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="sort_dir" class="form-input text-sm py-2 w-32">
            <option value="desc" {{ request('sort_dir', $sortDir) === 'desc' ? 'selected' : '' }}>Newest first</option>
            <option value="asc"  {{ request('sort_dir', $sortDir) === 'asc'  ? 'selected' : '' }}>Oldest first</option>
        </select>

        <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">

        <div class="flex gap-2">
            <button type="submit" class="btn-primary py-2 text-sm">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ $route }}" class="btn-secondary py-2 text-sm">Clear</a>
        </div>

    </form>
</div>
