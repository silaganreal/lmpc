<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends Controller
{
    /**
     * Display a listing of membership applications.
     */
    public function index(): Response
    {
        $applications = MembershipApplication::query()
            ->with('member')
            ->latest('date_of_application')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('MembershipApplications/Index', [
            'applications' => $applications,
        ]);
    }

    /**
     * Show the form for creating a new membership application.
     */
    public function create(): Response
    {
        $members = Member::query()
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'member_no',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'membership_type',
            ]);

        return Inertia::render('MembershipApplications/Create', [
            'members' => $members,
        ]);
    }

    /**
     * Store a newly created membership application.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'date_of_application' => ['required', 'date'],
            'membership_type' => ['required', 'in:regular,associate'],
            'shares_subscribed' => ['required', 'integer', 'min:1'],
            'amount_subscribed' => ['required', 'numeric', 'min:0'],
            'initial_paid_up' => ['required', 'numeric', 'min:0'],
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'recruiter_mobile' => ['nullable', 'string', 'max:30'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['application_no'] = $this->generateApplicationNumber();
        $validated['status'] = 'draft';

        $application = MembershipApplication::create($validated);

        return redirect()
            ->route('membership-applications.show', $application)
            ->with('success', 'Membership application created successfully.');
    }

    /**
     * Display the specified membership application.
     */
    public function show(
        MembershipApplication $membershipApplication
    ): Response {
        $membershipApplication->load([
            'member.addresses',
            'member.familyMembers',
            'member.educations',
            'member.employments',
            'documents',
            'processedBy',
            'approvedBy',
        ]);

        return Inertia::render('MembershipApplications/Show', [
            'application' => $membershipApplication,
        ]);
    }

    /**
     * Show the form for editing the specified membership application.
     */
    public function edit(
        MembershipApplication $membershipApplication
    ): Response {
        $members = Member::query()
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'member_no',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'membership_type',
            ]);

        return Inertia::render('MembershipApplications/Edit', [
            'application' => $membershipApplication,
            'members' => $members,
        ]);
    }

    /**
     * Update the specified membership application.
     */
    public function update(
        Request $request,
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        $validated = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'date_of_application' => ['required', 'date'],
            'membership_type' => ['required', 'in:regular,associate'],
            'shares_subscribed' => ['required', 'integer', 'min:1'],
            'amount_subscribed' => ['required', 'numeric', 'min:0'],
            'initial_paid_up' => ['required', 'numeric', 'min:0'],
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'recruiter_mobile' => ['nullable', 'string', 'max:30'],
            'remarks' => ['nullable', 'string'],
        ]);

        $membershipApplication->update($validated);

        return redirect()
            ->route('membership-applications.show', $membershipApplication)
            ->with('success', 'Membership application updated successfully.');
    }

    /**
     * Remove the specified membership application.
     */
    public function destroy(
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        $membershipApplication->update([
            'status' => 'cancelled',
        ]);

        return redirect()
            ->route('membership-applications.index')
            ->with('success', 'Membership application cancelled successfully.');
    }

    /**
     * Generate the next membership application number.
     */
    private function generateApplicationNumber(): string
    {
        $year = now()->year;

        $lastApplication = MembershipApplication::query()
            ->where('application_no', 'like', "APP-{$year}-%")
            ->latest('id')
            ->first();

        $nextNumber = $lastApplication
            ? ((int) substr($lastApplication->application_no, -6)) + 1
            : 1;

        return sprintf('APP-%d-%06d', $year, $nextNumber);
    }
}
