@extends('merchant.layout')

@section('title', 'Edit Individual')
@section('page-title', 'Edit Individual')
@section('page-subtitle', 'Update individual details')

@section('topbar-actions')
    <a href="{{ route('merchant.consumers.show', $consumer->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.consumers.update', $consumer->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="name" class="form-label">Full Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $consumer->name) }}" required
                    class="form-input @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $consumer->email) }}"
                    class="form-input @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="text" name="mobile_number" id="mobile_number" value="{{ old('mobile_number', $consumer->mobile_number) }}"
                    class="form-input @error('mobile_number') border-red-400 @enderror">
                @error('mobile_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if(isset($groups) && $groups->count() > 0)
            <div class="mb-6">
                <label for="group_ids" class="form-label">Groups <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="group_ids[]" id="group_ids" multiple
                    class="form-input @error('group_ids') border-red-400 @enderror" style="height:120px;">
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ in_array($group->id, old('group_ids', $consumer->groups->pluck('id')->toArray())) ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Hold Ctrl / Cmd to select multiple</p>
                @error('group_ids')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.consumers.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
