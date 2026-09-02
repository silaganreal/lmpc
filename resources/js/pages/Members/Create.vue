<script setup lang="ts">
    import { Link, useForm } from '@inertiajs/vue3';

    const form = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        suffix: '',
        date_of_birth: '',
        sex: '',
        civil_status: '',
        nationality: '',
        religion: '',
        place_of_birth: '',
        tin: '',
        mobile_number: '',
        telephone_number: '',
        email: '',
        residence_type: '',
        membership_type: '',
        date_joined: '',
        status: 'active',
    })

    const submit = () => {
        form.post('/members')
    }
</script>

<template>
    <div class="p-6">
        <div class="mb-6">
            <Link
                href="/members"
                class="text-sm text-muted-foreground hover:underline"
            >
                ← Back to Members
            </Link>

            <h1 class="mt-2 text-2xl fort-semibold">
                Add Member
            </h1>

            <p class="text-sm text-muted-foreground">
                Create a new cooperative member.
            </p>
        </div>

        <form
            @submit.prevent="submit"
            class="space-y-8"
        >
            <!-- Personal Information -->
            <section class="rounded-lg border p-6">
                <h2 class="mb-1 text-lg fort-semibold">
                    Personal Information
                </h2>

                <p class="mb-6 text-sm text-muted-foreground">
                    Basic information about the member.
                </p>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-1">
                        <label class="mb-2 block text-sm font-medium">First Name</label>
                        <input
                            v-model="form.first_name"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.first_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div class="lg:col-span-1">
                        <label class="mb-2 block text-sm font-medium">Middle Name</label>
                        <input
                            v-model="form.middle_name"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.middle_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.middle_name }}
                        </p>
                    </div>

                    <div class="lg:col-span-1">
                        <label class="mb-2 block text-sm font-medium">Last Name</label>
                        <input
                            v-model="form.last_name"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.last_name"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Suffix</label>
                        <input
                            v-model="form.suffix"
                            type="text"
                            placeholder="Jr., Sr., III"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Date of Birth</label>
                        <input
                            v-model="form.date_of_birth"
                            type="date"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Sex</label>
                        <select
                            v-model="form.sex"
                            class="w-full rounded-md border px-3 py-2"
                        >
                            <option value="">-Select sex-</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-3 block text-sm font-medium">Civil Status</label>
                        <select
                            v-model="form.civil_status"
                            class="w-full rounded-md border px-3 py-2"
                        >
                            <option value="">-Select status-</option>
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="widowed">Widowed</option>
                            <option value="separated">Separated</option>
                            <option value="divorced">Divorced</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Nationality</label>
                        <input
                            v-model="form.nationality"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Religion</label>
                        <input
                            v-model="form.religion"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Place of Birth</label>
                        <input
                            v-model="form.place_of_birth"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>
                </div>
            </section>

            <!-- Contact Information -->
            <section class="rounded-lg border p-6">
                <h2 class="mb-1 text-lg font-semibold">Contact Information</h2>

                <p class="mb-6 text-sm text-muted-foreground">
                    Contact details of the member.
                </p>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Mobile Number</label>
                        <input
                            v-model="form.mobile_number"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Telephone Number</label>
                        <input
                            v-model="form.telephone_number"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-md border px-3 py-2"
                        />
                        <p
                            v-if="form.errors.email"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">TIN</label>
                        <input
                            v-model="form.tin"
                            type="text"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Residence Type</label>
                        <input
                            v-model="form.residence_type"
                            type="text"
                            placeholder="Owned, Rented, etc."
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>
                </div>
            </section>

            <!-- Membership Information -->
            <section class="rounded-lg border p-6">
                <h2 class="mb-1 text-lg font-semibold">Membership Information</h2>
                <p class="mb-6 text-sm text-muted-foreground">
                    Cooperative membership details.
                </p>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Membership Type</label>
                        <select
                            v-model="form.membership_type"
                            class="w-full rounded-md border px-3 py-2"
                        >
                            <option value="">-Select membership type-</option>
                            <option value="regular">Regular</option>
                            <option value="associate">Associate</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Date Joined</label>
                        <input
                            v-model="form.date_joined"
                            type="date"
                            class="w-full rounded-md border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-md border px-3 py-2"
                        >
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended </option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Actions -->
            <div class="flex justify-end gap-3">
                <Link
                    href="/members"
                    class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                >
                    Cancel
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                >
                    {{ form.processing ? 'Creating...' : 'Create Member' }}
                </button>
            </div>

        </form>
    </div>
</template>