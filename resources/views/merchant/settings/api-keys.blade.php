@extends('merchant.layout')

@section('title', 'Settings — API & Integrations')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your account and business details')

@section('content')

@include('merchant.settings._tabs')

{{-- New key reveal banner (shown once after generation) --}}
@if(session('new_key'))
<div class="mb-6 rounded-xl border-2 p-5" style="background:#fffbeb;border-color:#f59e0b;">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-triangle mt-0.5" style="color:#d97706;"></i>
        <div class="flex-1">
            <p class="font-semibold text-sm mb-1" style="color:#92400e;">
                Save your API key — it will not be shown again
            </p>
            <p class="text-xs mb-3" style="color:#a16207;">
                This is the only time "{{ session('new_key_name') }}" will be displayed in full. Copy it now and store it securely (e.g. in your server's environment variables).
            </p>
            <div class="flex items-center gap-3">
                <code class="flex-1 px-4 py-3 rounded-lg text-sm font-mono break-all"
                    style="background:#fef3c7;color:#1c1917;border:1px solid #fcd34d;"
                    id="newKeyValue">{{ session('new_key') }}</code>
                <button onclick="copyNewKey()" id="copyNewKeyBtn"
                    class="btn-primary flex-shrink-0 text-sm py-2 px-4">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('success'))
<div class="alert-success mb-5 flex items-center gap-3">
    <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert-error mb-5 flex items-center gap-3">
    <i class="fas fa-exclamation-circle text-red-500"></i> {{ session('error') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: key list --}}
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Active API Keys</h3>
                <span class="text-xs text-gray-400">{{ $keys->count() }} / 5</span>
            </div>

            @if($keys->isEmpty())
            <div class="text-center py-14">
                <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                    <i class="fas fa-key"></i>
                </div>
                <p class="text-sm font-medium text-gray-700 mb-1">No API keys yet</p>
                <p class="text-xs text-gray-400">Generate your first key to start integrating Edfundo Pay into your platform or website.</p>
            </div>
            @else
            <table class="data-table w-full">
                <thead>
                    <tr>
                        <th class="text-left">Name</th>
                        <th class="text-left">Key</th>
                        <th class="text-left">Last Used</th>
                        <th class="text-left">Created</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keys as $key)
                    <tr>
                        <td class="font-semibold text-sm text-gray-800">{{ $key->name }}</td>
                        <td>
                            <code class="text-xs font-mono px-2 py-1 rounded"
                                style="background:#f3f4f6;color:#374151;">{{ $key->key_prefix }}</code>
                        </td>
                        <td class="text-sm text-gray-500">
                            {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="text-sm text-gray-500">{{ $key->created_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <form method="POST"
                                action="{{ route('merchant.settings.api-keys.destroy', $key->id) }}"
                                onsubmit="return confirm('Revoke \'{{ addslashes($key->name) }}\'? Any integrations using this key will stop working immediately.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                                    style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                                    <i class="fas fa-ban mr-1"></i> Revoke
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    {{-- Right: generate form + info --}}
    <div class="space-y-5">
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Generate New Key</h3>
            @if($keys->count() >= 5)
            <p class="text-xs text-gray-400">You have reached the maximum of 5 active keys. Revoke an existing key to generate a new one.</p>
            @else
            <form method="POST" action="{{ route('merchant.settings.api-keys.store') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Key Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required
                        value="{{ old('name') }}"
                        class="form-input @error('name') border-red-400 @enderror"
                        placeholder="e.g. Production, My Website">
                    <p class="text-xs text-gray-400 mt-1">A label to help you identify where this key is used.</p>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary w-full justify-center">
                    <i class="fas fa-plus"></i> Generate Key
                </button>
            </form>
            @endif
        </div>

        <div class="card p-5" style="background:linear-gradient(135deg,rgba(61,1,189,0.04),rgba(0,189,255,0.04));">
            <p class="text-xs font-semibold text-gray-600 mb-2">
                <i class="fas fa-shield-alt mr-1" style="color:#3d01bd;"></i> Security tips
            </p>
            <ul class="text-xs text-gray-500 space-y-2">
                <li>• Store keys in environment variables, never in source code</li>
                <li>• Use one key per integration so you can revoke individually</li>
                <li>• Revoke any key you suspect has been exposed immediately</li>
                <li>• Keys have full access to your merchant account via the API</li>
            </ul>
        </div>

        <div class="card p-5">
            <p class="text-xs font-semibold text-gray-600 mb-2">
                <i class="fas fa-code mr-1 gradient-text"></i> How to authenticate
            </p>
            <p class="text-xs text-gray-500 mb-3">Include your key in the <code class="px-1 py-0.5 rounded text-xs" style="background:#f3f4f6;">Authorization</code> header of every API request:</p>
            <code class="block text-xs font-mono p-3 rounded-lg break-all" style="background:#f8f9fc;color:#374151;border:1px solid #eef0f5;">
                Authorization: Bearer epk_live_…
            </code>
        </div>
    </div>

</div>

@push('scripts')
<script>
function copyNewKey() {
    const key  = document.getElementById('newKeyValue').textContent.trim();
    const btn  = document.getElementById('copyNewKeyBtn');
    navigator.clipboard.writeText(key).then(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2500);
    });
}
</script>
@endpush

@endsection
