@php
    $isOwner = ($teamRole ?? 'owner') === 'owner';
    $tabs = [
        ['route' => 'merchant.settings.profile',  'icon' => 'fa-building', 'label' => 'Business Profile', 'owner' => true],
        ['route' => 'merchant.team.index',        'icon' => 'fa-user-group', 'label' => 'Team',            'owner' => true],
        ['route' => 'merchant.settings.password',  'icon' => 'fa-lock',     'label' => 'Change Password',  'owner' => false],
        ['route' => 'merchant.settings.api-keys',  'icon' => 'fa-key',      'label' => 'API & Integrations', 'owner' => true],
    ];
    $tabs = array_values(array_filter($tabs, fn ($t) => $isOwner || !$t['owner']));
@endphp
<div class="flex gap-1 mb-6 border-b border-gray-200">
    @foreach($tabs as $tab)
    @php $active = request()->routeIs($tab['route']); @endphp
    <a href="{{ route($tab['route']) }}"
        class="pb-3 px-4 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap"
        style="{{ $active ? 'border-color:#3d01bd;color:#3d01bd;' : 'border-color:transparent;color:#6b7280;' }}">
        <i class="fas {{ $tab['icon'] }} mr-1.5"></i>{{ $tab['label'] }}
    </a>
    @endforeach
</div>
