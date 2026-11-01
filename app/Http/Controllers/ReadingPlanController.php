<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $currentStatus = $request->status;

        $readingPlans = ReadingPlan::query()
            ->with('book')
            ->where('user_id', auth()->id())
            ->when(
                $currentStatus,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->orderBy('target_date')
            ->get();

        return view(
            'reading-plans.index',
            compact('readingPlans', 'currentStatus')
        );
    }

    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(
        StoreReadingPlanRequest $request
    ): RedirectResponse {
        ReadingPlan::create([
            'user_id' => $request->user()->id,
            'book_id' => $request->validated('book_id'),
            'target_date' => $request->validated('target_date'),
            'status' => ReadingPlanStatus::Pending,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }

    public function edit(ReadingPlan $readingPlan): View
    {
        $this->ensureOwner($readingPlan);

        $readingPlan->load('book');

        return view(
            'reading-plans.edit',
            compact('readingPlan')
        );
    }

    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->ensureOwner($readingPlan);

        $readingPlan->update([
            'target_date' => $request->validated('target_date'),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function complete(
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->ensureOwner($readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読了しました。');
    }

    public function destroy(
        ReadingPlan $readingPlan
    ): RedirectResponse {
        $this->ensureOwner($readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    private function ensureOwner(ReadingPlan $readingPlan): void
    {
        abort_unless(
            $readingPlan->user_id === auth()->id(),
            403
        );
    }
}
