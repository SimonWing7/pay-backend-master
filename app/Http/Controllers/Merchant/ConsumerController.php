<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\ConsumerService;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsumerController extends Controller
{
    public function __construct(
        protected ConsumerService $consumerService,
        protected GroupService $groupService
    ) {
    }

    public function index(Request $request): View
    {
        $merchantId = $request->user()->id;
        $filters = [
            'search' => $request->get('search'),
            'group_id' => $request->get('group_id'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $consumers = $this->consumerService->getAllByMerchant($merchantId, $filters, $sortBy, $sortDir, $perPage);
        $groups = $this->groupService->getAllByMerchant($merchantId);
        return view('merchant.consumers.index', compact('consumers', 'groups'));
    }

    public function create(Request $request): View
    {
        $merchantId = $request->user()->id;
        $groups = $this->groupService->getAllByMerchant($merchantId);
        return view('merchant.consumers.create', compact('groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'mobile_number' => 'nullable|string',
            'group_ids' => 'nullable|array',
            'group_ids.*' => Rule::exists('groups', 'id')->where(
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

        $this->consumerService->create($data);

        return redirect()->route('merchant.consumers.index')
            ->with('success', 'Consumer created successfully');
    }

    public function show(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $consumer = $this->consumerService->getById($id, $merchantId);

        if (!$consumer) {
            abort(404, 'Consumer not found');
        }

        return view('merchant.consumers.show', compact('consumer'));
    }

    public function edit(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $consumer = $this->consumerService->getById($id, $merchantId);

        if (!$consumer) {
            abort(404, 'Consumer not found');
        }

        $groups = $this->groupService->getAllByMerchant($merchantId);
        return view('merchant.consumers.edit', compact('consumer', 'groups'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $consumer = $this->consumerService->getById($id, $merchantId);

        if (!$consumer) {
            abort(404, 'Consumer not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'mobile_number' => 'nullable|string',
            'group_ids' => 'nullable|array',
            'group_ids.*' => Rule::exists('groups', 'id')->where(
                fn ($query) => $query->where('merchant_id', $request->user()->id)
            ),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->consumerService->update($consumer, $validator->validated());

        return redirect()->route('merchant.consumers.index')
            ->with('success', 'Consumer updated successfully');
    }

    public function delete(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $consumer = $this->consumerService->getById($id, $merchantId);

        if (!$consumer) {
            abort(404, 'Consumer not found');
        }

        $this->consumerService->delete($consumer);

        return redirect()->route('merchant.consumers.index')
            ->with('success', 'Consumer deleted successfully');
    }
}
