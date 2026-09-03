<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
// use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(): Response
    {
        $members = Member::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Members/Index', [
            'members' => $members,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Members/Create');
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['member_no'] = $this->generateMemberNumber();

        Member::create($validated);

        return redirect()->route('members.index')->with('success', 'Member created successfully.');
    }

    public function show(Member $member): Response
    {
        $member->load([
            'addresses',
            'familyMembers',
            'educations',
            'employments',
            'shareAccount',
            'cbuAccount',
        ]);

        return Inertia::render('Members/Show', [
            'member' => $member,
        ]);
    }

    public function edit(Member $member): Response
    {
        return Inertia::render('Members/Edit', [
            'member' => $member,
        ]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $validated = $request->validated();
        $member->update($validated);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->update([
            'status' => 'inactive',
        ]);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deactivated successfully.');
    }

    public function reactivate(Member $member): RedirectResponse
    {
        $member->update([
            'status' => 'active',
        ]);

        return redirect()
            ->route('members.show', $member)
            ->with('success', 'Member reactivated successfully.');
    }

    private function generateMemberNumber(): string
    {
        $year = now()->year;

        $lastMember = Member::query()
            ->where('member_no', 'like', "M-{$year}-%")
            ->orderBy('id')
            ->first();

        $nextNumber = $lastMember
            ? ((int) substr($lastMember->member_no, -6)) + 1
            : 1;

        return sprintf('M-%d-%06d', $year, $nextNumber);
    }
}
