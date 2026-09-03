<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Member {
    id: number;
    member_no: string;
    application_no: string | null;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    suffix: string | null;
    date_of_birth: string | null;
    sex: string;
    civil_status: string;
    nationality: string | null;
    religion: string | null;
    place_of_birth: string | null;
    tin: string | null;
    mobile_number: string | null;
    telephone_number: string | null;
    email: string | null;
    residence_type: string | null;
    membership_type: string;
    date_joined: string | null;
    status: string;
}

const props = defineProps<{
    member: Member;
}>();

const form = useForm({
    first_name: props.member.first_name ?? '',
    middle_name: props.member.middle_name ?? '',
    last_name: props.member.last_name ?? '',
    suffix: props.member.suffix ?? '',
    date_of_birth: props.member.date_of_birth ?? '',
    sex: props.member.sex ?? '',
    civil_status: props.member.civil_status ?? '',
    nationality: props.member.nationality ?? '',
    religion: props.member.religion ?? '',
    place_of_birth: props.member.place_of_birth ?? '',
    tin: props.member.tin ?? '',
    mobile_number: props.member.mobile_number ?? '',
    telephone_number: props.member.telephone_number ?? '',
    email: props.member.email ?? '',
    residence_type: props.member.residence_type ?? '',
    membership_type: props.member.membership_type ?? '',
    date_joined: props.member.date_joined ?? '',
    status: props.member.status ?? 'pending',
});

const submit = () => {
    form.put(`/members/${props.member.id}`);
};
</script>

<template>
    <Head :title="`Edit Member - ${member.member_no}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <!-- Header -->
        <div>
            <div class="mb-2">
                <Link
                    :href="`/members/${member.id}`"
                    class="text-primary text-sm font-medium hover:underline"
                >
                    ← Back to Member Profile
                </Link>
            </div>

            <h1 class="text-primary text-2xl font-semibold tracking-tight">
                Edit Member
            </h1>

            <p class="text-muted-foreground mt-1 text-sm">
                Update the member's personal and membership information.
            </p>
        </div>

        <!-- Form -->
        <form
            @submit.prevent="submit"
            class="bg-card overflow-hidden rounded-xl border shadow-sm"
        >
            <!-- Member Information -->
            <div class="bg-muted/30 border-b px-5 py-4">
                <h2 class="font-semibold">Member Information</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    Member No. {{ member.member_no }}
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- First Name -->
                <div>
                    <label
                        for="first_name"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        First Name
                    </label>

                    <input
                        id="first_name"
                        v-model="form.first_name"
                        type="text"
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
                        Last Name
                    </label>

                    <input
                        id="last_name"
                        v-model="form.last_name"
                        type="text"
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
                <div>
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
            </div>

            <!-- Contact Information -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Contact Information</h2>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Mobile -->
                <div>
                    <label
                        for="mobile_number"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Mobile Number
                    </label>

                    <input
                        id="mobile_number"
                        v-model="form.mobile_number"
                        type="text"
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
                        Telephone Number
                    </label>

                    <input
                        id="telephone_number"
                        v-model="form.telephone_number"
                        type="text"
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
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.email"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <!-- Residence Type -->
                <div>
                    <label
                        for="residence_type"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Residence Type
                    </label>

                    <input
                        id="residence_type"
                        v-model="form.residence_type"
                        type="text"
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

            <!-- Membership Information -->
            <div class="bg-muted/30 border-y px-5 py-4">
                <h2 class="font-semibold">Membership Information</h2>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
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

                <!-- Date Joined -->
                <div>
                    <label
                        for="date_joined"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Date Joined
                    </label>

                    <input
                        id="date_joined"
                        v-model="form.date_joined"
                        type="date"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    />

                    <p
                        v-if="form.errors.date_joined"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.date_joined }}
                    </p>
                </div>

                <!-- Status -->
                <div>
                    <label
                        for="status"
                        class="mb-1.5 block text-sm font-medium"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        v-model="form.status"
                        class="border-input bg-background focus:ring-primary w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                    >
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                    </select>

                    <p
                        v-if="form.errors.status"
                        class="text-destructive mt-1 text-sm"
                    >
                        {{ form.errors.status }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div
                class="bg-muted/20 flex flex-col-reverse gap-3 border-t px-5 py-4 sm:flex-row sm:justify-end"
            >
                <Link
                    :href="`/members/${member.id}`"
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
