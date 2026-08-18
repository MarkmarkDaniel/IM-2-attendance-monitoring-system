<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\Department;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(Request $request): View
    {
        $search       = $request->query('search');
        $departmentId = $request->query('department_id');

        $subjects = Subject::query()
            ->with('department')
            ->search($search)
            ->when($departmentId, fn ($q, $v) => $q->where('department_id', $v))
            ->withCount('classes')
            ->orderBy('subject_code')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('department_code')->get();

        return view('admin.subjects.index', compact('subjects', 'departments', 'search', 'departmentId'));
    }

    public function create(): View
    {
        return view('admin.subjects.create', [
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $subject = Subject::create($request->validated());

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject "' . $subject->subject_code . '" was created.');
    }

    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject'     => $subject,
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function update(SubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject "' . $subject->subject_code . '" was updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        if ($subject->classes()->exists()) {
            return redirect()->route('admin.subjects.index')
                ->with('error', 'Cannot delete "' . $subject->subject_code
                    . '" because classes are still offered under it.');
        }

        $code = $subject->subject_code;
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject "' . $code . '" was deleted.');
    }
}
