<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\GroupService;
use App\Services\ConsumerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService,
        protected ConsumerService $consumerService
    ) {
    }

    public function index(Request $request): View
    {
        $merchantId = $request->user()->id;
        $groups = $this->groupService->getAllByMerchant($merchantId)->load('consumers');
        return view('merchant.groups.index', compact('groups'));
    }

    public function create(Request $request): View
    {
        $merchantId = $request->user()->id;
        $consumers = \App\Models\Consumer::where('merchant_id', $merchantId)->orderBy('name', 'asc')->get();
        return view('merchant.groups.create', compact('consumers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'consumer_ids' => 'nullable|array',
            'consumer_ids.*' => Rule::exists('consumers', 'id')->where(
                fn ($query) => $query->where('merchant_id', $request->user()->id)
            ),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['merchant_id'] = $request->user()->id;
        
        $consumerIds = $data['consumer_ids'] ?? [];
        unset($data['consumer_ids']);

        $group = $this->groupService->create($data);
        
        if (!empty($consumerIds)) {
            $group->consumers()->sync($consumerIds);
        }

        return redirect()->route('merchant.groups.index')
            ->with('success', 'Group created successfully');
    }

    public function show(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $group = $this->groupService->getById($id, $merchantId);

        if (!$group) {
            abort(404, 'Group not found');
        }

        $group->load('consumers', 'invoices.consumer');
        return view('merchant.groups.show', compact('group'));
    }

    public function edit(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $group = $this->groupService->getById($id, $merchantId);

        if (!$group) {
            abort(404, 'Group not found');
        }

        $merchantId = $request->user()->id;
        $consumers = \App\Models\Consumer::where('merchant_id', $merchantId)->orderBy('name', 'asc')->get();
        $group->load('consumers');
        
        return view('merchant.groups.edit', compact('group', 'consumers'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $group = $this->groupService->getById($id, $merchantId);

        if (!$group) {
            abort(404, 'Group not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'consumer_ids' => 'nullable|array',
            'consumer_ids.*' => Rule::exists('consumers', 'id')->where(
                fn ($query) => $query->where('merchant_id', $request->user()->id)
            ),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $consumerIds = $data['consumer_ids'] ?? null;
        unset($data['consumer_ids']);

        $this->groupService->update($group, $data);
        
        if ($consumerIds !== null) {
            $group->consumers()->sync($consumerIds);
        }

        return redirect()->route('merchant.groups.index')
            ->with('success', 'Group updated successfully');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $group = $this->groupService->getById($id, $merchantId);

        if (!$group) {
            abort(404, 'Group not found');
        }

        $this->groupService->delete($group);

        return redirect()->route('merchant.groups.index')
            ->with('success', 'Group deleted successfully');
    }
}
