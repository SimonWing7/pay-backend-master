{{-- Expects: $step. Every button submits the step; "action" says where to go next. --}}
<div class="flex flex-wrap items-center justify-between gap-3 mt-8 pt-6" style="border-top:1px solid #f3f4f6;">
    <div class="flex items-center gap-1">
        @if($step !== 'welcome')
            <button type="submit" name="action" value="back" class="btn-ghost" formnovalidate>&larr; Back</button>
        @endif
        <button type="submit" name="action" value="later" class="btn-link" formnovalidate>Save &amp; finish later</button>
    </div>
    <button type="submit" name="action" value="next" class="btn-gradient">{{ $nextLabel ?? 'Save & continue' }} &rarr;</button>
</div>
