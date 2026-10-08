<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

interface MembershipApplication {
    id: number;
    application_no: string;

    // Applicant Information
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    date_of_birth: string | null;
    sex: string | null;
    civil_status: string | null;
    nationality: string | null;
    religion: string | null;
    place_of_birth: string | null;
    tin: string | null;
    mobile_number: string | null;
    telephone_number: string | null;
    email: string | null;
    residence_type: string | null;

    // Application Information
    date_of_application: string;
    membership_type: 'regular' | 'associate';

    // Share Capital
    shares_subscribed: number;
    amount_subscribed: string | number;
    initial_paid_up: string | number;

    // Recruitment
    recruiter_name: string | null;
    recruiter_mobile: string | null;

    remarks: string | null;
}

interface Props {
    application: MembershipApplication;
}

const props = defineProps<Props>();

const form = useForm({
    // Applicant Information
    first_name: props.application.first_name ?? '',
    middle_name: props.application.middle_name ?? '',
    last_name: props.application.last_name ?? '',
    suffix: props.application.suffix ?? '',
    date_of_birth: props.application.date_of_birth ?? '',
    sex: props.application.sex ?? '',
    civil_status: props.application.civil_status ?? '',
    nationality: props.application.nationality ?? '',
    religion: props.application.religion ?? '',
    place_of_birth: props.application.place_of_birth ?? '',
    tin: props.application.tin ?? '',
    mobile_number: props.application.mobile_number ?? '',
    telephone_number: props.application.telephone_number ?? '',
    email: props.application.email ?? '',
    residence_type: props.application.residence_type ?? '',

    // Application Information
    date_of_application: props.application.date_of_application ?? '',
    membership_type: props.application.membership_type ?? '',

    // Share Capital
    shares_subscribed: props.application.shares_subscribed ?? 1,
    amount_subscribed: Number(props.application.amount_subscribed ?? 0),
    initial_paid_up: Number(props.application.initial_paid_up ?? 0),

    // Recruitment
    recruiter_name: props.application.recruiter_name ?? '',
    recruiter_mobile: props.application.recruiter_mobile ?? '',

    // Remarks
    remarks: props.application.remarks ?? '',
});

const submit = () => {
    form.put(`/membership-applications/${props.application.id}`);
};
</script>

<template>
    <Head :title="`Edit Application ${application.application_no}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div>
            <div class="mb-2">
                <Link
                    :href="`/membership-applications/${application.id}`"
                    class="text-primary text-sm font-medium hover:underline"
                >
                    ← Back to Application
                </Link>
            </div>

            <h1 class="text-primary text-2xl font-semibold tracking-tight">
                Edit Membership Application
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Update applicant and membership application information.
            </p>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="bg-card overflow-hidden rounded-xl border shadow-sm"
        >
            <!-- Applicant Information -->
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Applicant Information</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Update the personal information of the membership applicant.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- First Name -->
                <div>
                    <label
                        for="first_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        First Name <span class="text-destructive">*</span>
                    </label>

                    <input
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.first_name"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.first_name }}
                    </p>
                </div>

                <!-- Middle Name -->
                <div>
                    <label
                        for="middle_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Middle Name
                    </label>

                    <input
                        id="middle_name"
                        v-model="form.middle_name"
                        type="text"
                        autocomplete="additional-name"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.middle_name"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.middle_name }}
                    </p>
                </div>

                <!-- Last Name -->
                <div>
                    <label
                        for="last_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Last Name <span class="text-destructive">*</span>
                    </label>

                    <input
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.last_name"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.last_name }}
                    </p>
                </div>

                <!-- Suffix -->
                <div>
                    <label
                        for="suffix"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Suffix
                    </label>

                    <input
                        id="suffix"
                        v-model="form.suffix"
                        type="text"
                        placeholder="Jr., Sr., III"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.suffix"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.suffix }}
                    </p>
                </div>

                <!-- Date of Birth -->
                <div>
                    <label
                        for="date_of_birth"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Date of Birth
                    </label>

                    <input
                        id="date_of_birth"
                        v-model="form.date_of_birth"
                        type="date"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.date_of_birth"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.date_of_birth }}
                    </p>
                </div>

                <!-- Sex -->
                <div>
                    <label for="sex" class="mb-1.5 block text-sm font-medium">
                        Sex
                    </label>

                    <select
                        id="sex"
                        v-model="form.sex"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="">Select sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>

                    <p
                        v-if="form.errors.sex"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.sex }}
                    </p>
                </div>

                <!-- Civil Status -->
                <div>
                    <label
                        for="civil_status"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Civil Status
                    </label>

                    <select
                        id="civil_status"
                        v-model="form.civil_status"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="">Select civil status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                        <option value="separated">Separated</option>
                        <option value="divorced">Divorced</option>
                    </select>

                    <p
                        v-if="form.errors.civil_status"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.civil_status }}
                    </p>
                </div>

                <!-- Nationality -->
                <div>
                    <label
                        for="nationality"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Nationality
                    </label>

                    <input
                        id="nationality"
                        v-model="form.nationality"
                        type="text"
                        placeholder="e.g. Filipino"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.nationality"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.nationality }}
                    </p>
                </div>

                <!-- Religion -->
                <div>
                    <label
                        for="religion"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Religion
                    </label>

                    <input
                        id="religion"
                        v-model="form.religion"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.religion"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.religion }}
                    </p>
                </div>

                <!-- Place of Birth -->
                <div class="sm:col-span-2">
                    <label
                        for="place_of_birth"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Place of Birth
                    </label>

                    <input
                        id="place_of_birth"
                        v-model="form.place_of_birth"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.place_of_birth"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.place_of_birth }}
                    </p>
                </div>

                <!-- TIN -->
                <div>
                    <label for="tin" class="mb-1.5 block text-sm font-medium">
                        TIN
                    </label>

                    <input
                        id="tin"
                        v-model="form.tin"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.tin"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.tin }}
                    </p>
                </div>

                <!-- Mobile -->
                <div>
                    <label
                        for="mobile_number"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Mobile / Cellphone
                    </label>

                    <input
                        id="mobile_number"
                        v-model="form.mobile_number"
                        type="text"
                        autocomplete="tel"
                        placeholder="09XXXXXXXXX"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.mobile_number"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.mobile_number }}
                    </p>
                </div>

                <!-- Telephone -->
                <div>
                    <label
                        for="telephone_number"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Telephone
                    </label>

                    <input
                        id="telephone_number"
                        v-model="form.telephone_number"
                        type="text"
                        autocomplete="tel"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.telephone_number"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.telephone_number }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium">
                        Email
                    </label>

                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.email"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Residence -->
                <div>
                    <label
                        for="residence_type"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Type of Residence
                    </label>

                    <input
                        id="residence_type"
                        v-model="form.residence_type"
                        type="text"
                        placeholder="e.g. Owned, Rented"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.residence_type"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.residence_type }}
                    </p>
                </div>
            </div>

            <!-- Application Information -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Application Information</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Update the membership application details.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Date -->
                <div>
                    <label
                        for="date_of_application"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Date of Application
                    </label>

                    <input
                        id="date_of_application"
                        v-model="form.date_of_application"
                        type="date"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.date_of_application"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.date_of_application }}
                    </p>
                </div>

                <!-- Membership Type -->
                <div>
                    <label
                        for="membership_type"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Membership Type
                    </label>

                    <select
                        id="membership_type"
                        v-model="form.membership_type"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="">Select membership type</option>
                        <option value="regular">Regular</option>
                        <option value="associate">Associate</option>
                    </select>

                    <p
                        v-if="form.errors.membership_type"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.membership_type }}
                    </p>
                </div>
            </div>

            <!-- Share Capital -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Share Capital</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Update the applicant's subscribed shares and initial
                    payment.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Shares -->
                <div>
                    <label
                        for="shares_subscribed"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Shares Subscribed
                    </label>

                    <input
                        id="shares_subscribed"
                        v-model.number="form.shares_subscribed"
                        type="number"
                        min="1"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.shares_subscribed"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.shares_subscribed }}
                    </p>
                </div>

                <!-- Amount Subscribed -->
                <div>
                    <label
                        for="amount_subscribed"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Amount Subscribed
                    </label>

                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                        >
                            ₱
                        </span>

                        <input
                            id="amount_subscribed"
                            v-model.number="form.amount_subscribed"
                            type="number"
                            min="0"
                            step="0.01"
                            class="border-input bg-background focus:ring-primary w-full rounded-md border py-2 pr-3 pl-8 text-sm outline-none focus:ring-2"
                        />
                    </div>

                    <p
                        v-if="form.errors.amount_subscribed"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.amount_subscribed }}
                    </p>
                </div>

                <!-- Initial Paid-Up -->
                <div>
                    <label
                        for="initial_paid_up"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Initial Paid-Up
                    </label>

                    <div class="relative">
                        <span
                            class="text-muted-foreground absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                        >
                            ₱
                        </span>

                        <input
                            id="initial_paid_up"
                            v-model.number="form.initial_paid_up"
                            type="number"
                            min="0"
                            step="0.01"
                            class="border-input bg-background focus:ring-primary w-full rounded-md border py-2 pr-3 pl-8 text-sm outline-none focus:ring-2"
                        />
                    </div>

                    <p
                        v-if="form.errors.initial_paid_up"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.initial_paid_up }}
                    </p>
                </div>
            </div>

            <!-- Recruitment -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Recruitment Information</h2>

                <p class="text-muted-foreground mt-1 text-sm">
                    Update the person who recruited or referred the applicant.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <!-- Recruiter Name -->
                <div>
                    <label
                        for="recruiter_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Recruiter Name
                    </label>

                    <input
                        id="recruiter_name"
                        v-model="form.recruiter_name"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.recruiter_name"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.recruiter_name }}
                    </p>
                </div>

                <!-- Recruiter Mobile -->
                <div>
                    <label
                        for="recruiter_mobile"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Recruiter Mobile
                    </label>

                    <input
                        id="recruiter_mobile"
                        v-model="form.recruiter_mobile"
                        type="text"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.recruiter_mobile"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.recruiter_mobile }}
                    </p>
                </div>
            </div>

            <!-- Remarks -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Remarks</h2>
            </div>

            <div class="p-5">
                <label for="remarks" class="mb-1.5 block text-sm font-medium">
                    Remarks
                </label>

                <textarea
                    id="remarks"
                    v-model="form.remarks"
                    rows="4"
                    class="border-input bg-background focus:ring-primary w-full resize-y rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    placeholder="Additional notes or remarks..."
                />

                <p
                    v-if="form.errors.remarks"
                    class="text-destructive mt-1 text-sm"
                >
                    {{ form.errors.remarks }}
                </p>
            </div>

            <!-- Actions -->
            <div
                class="bg-muted/20 flex flex-col-reverse gap-3 border-t px-5 py-4 sm:flex-row sm:justify-end"
            >
                <Link
                    :href="`/membership-applications/${application.id}`"
                    class="border-input hover:bg-muted inline-flex items-center justify-center rounded-md border px-4 py-2 text-sm font-medium transition"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="bg-primary text-primary-foreground inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
