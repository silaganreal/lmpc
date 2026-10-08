<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipApplication;
use App\Services\Membership\MembershipApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MembershipApplicationController extends Controller
{
    public function __construct(
        private MembershipApplicationService $membershipApplicationService
    ) {}

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
            // Applicant information
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],

            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:male,female'],
            'civil_status' => [
                'nullable',
                'in:single,married,widowed,separated,divorced',
            ],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:50'],

            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'residence_type' => ['nullable', 'string', 'max:100'],

            // Application information
            'member_id' => ['nullable', 'exists:members,id'],
            'date_of_application' => ['required', 'date'],
            'membership_type' => ['required', 'in:regular,associate'],
            'shares_subscribed' => ['required', 'integer', 'min:1'],
            'amount_subscribed' => ['required', 'numeric', 'min:0'],
            'initial_paid_up' => ['required', 'numeric', 'min:0'],

            // Recruitment
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'recruiter_mobile' => ['nullable', 'string', 'max:30'],

            'remarks' => ['nullable', 'string'],
        ]);

        $application = $this->membershipApplicationService->createApplication($validated);

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
     * Submit draft applications.
     */
    public function submit(
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        $this->membershipApplicationService
            ->submitApplication($membershipApplication);

        return redirect()
            ->route('membership-applications.show', $membershipApplication)
            ->with('success', 'Membership application submitted successfully.');
    }

    /**
     * Show the form for editing the specified membership application.
     */
    // public function edit(
    //     MembershipApplication $membershipApplication
    // ): Response {
    //     $members = Member::query()
    //         ->whereIn('status', ['pending', 'active'])
    //         ->orderBy('last_name')
    //         ->orderBy('first_name')
    //         ->get([
    //             'id',
    //             'member_no',
    //             'first_name',
    //             'middle_name',
    //             'last_name',
    //             'suffix',
    //             'membership_type',
    //         ]);

    //     return Inertia::render('MembershipApplications/Edit', [
    //         'application' => $membershipApplication,
    //         'members' => $members,
    //     ]);
    // }
    public function edit(MembershipApplication $membershipApplication): Response
    {
        return Inertia::render('MembershipApplications/Edit', [
            'application' => $membershipApplication,
        ]);
    }

    /**
     * Update the specified membership application.
     */
    // public function update(
    //     Request $request,
    //     MembershipApplication $membershipApplication
    // ): RedirectResponse {
    //     $validated = $request->validate([
    //         'member_id' => ['nullable', 'exists:members,id'],
    //         'date_of_application' => ['required', 'date'],
    //         'membership_type' => ['required', 'in:regular,associate'],
    //         'shares_subscribed' => ['required', 'integer', 'min:1'],
    //         'amount_subscribed' => ['required', 'numeric', 'min:0'],
    //         'initial_paid_up' => ['required', 'numeric', 'min:0'],
    //         'recruiter_name' => ['nullable', 'string', 'max:255'],
    //         'recruiter_mobile' => ['nullable', 'string', 'max:30'],
    //         'remarks' => ['nullable', 'string'],
    //     ]);

    //     $membershipApplication->update($validated);

    //     return redirect()
    //         ->route('membership-applications.show', $membershipApplication)
    //         ->with('success', 'Membership application updated successfully.');
    // }
    public function update(
        Request $request,
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        if ($membershipApplication->status !== 'draft') {
            return redirect()
                ->route('membership-applications.show', $membershipApplication)
                ->with(
                    'error',
                    'Only draft membership applications can be edited.'
                );
        }

        $validated = $request->validate([
            // Applicant information
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:20'],

            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:male,female'],
            'civil_status' => [
                'nullable',
                'in:single,married,widowed,separated,divorced',
            ],
            'nationality' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:50'],

            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'residence_type' => ['nullable', 'string', 'max:100'],

            // Application information
            'date_of_application' => ['required', 'date'],
            'membership_type' => ['required', 'in:regular,associate'],

            // Share capital
            'shares_subscribed' => ['required', 'integer', 'min:1'],
            'amount_subscribed' => ['required', 'numeric', 'min:0'],
            'initial_paid_up' => ['required', 'numeric', 'min:0'],

            // Recruitment
            'recruiter_name' => ['nullable', 'string', 'max:255'],
            'recruiter_mobile' => ['nullable', 'string', 'max:30'],

            // Remarks
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
     * Move a submitted application into review.
     */
    public function review(
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        $this->membershipApplicationService->startReview(
            $membershipApplication,
            (int) auth()->id()
        );

        return redirect()
            ->route(
                'membership-applications.show',
                $membershipApplication
            )
            ->with(
                'success',
                'Membership application is now under review.'
            );
    }

    /**
     * Approve a membership application.
     */
    public function approve(
        MembershipApplication $membershipApplication
    ): RedirectResponse {
        $this->membershipApplicationService->approveApplication(
            $membershipApplication,
            (int) auth()->id()
        );

        return redirect()
            ->route(
                'membership-applications.show',
                $membershipApplication
            )
            ->with(
                'success',
                'Membership application approved successfully.'
            );
    }
}
