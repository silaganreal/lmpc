<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
{
    /**
     * Accounting and Superadmin roles.
     */
    private function canManageAccounting(User $user): bool
    {
        return $user->hasRole('accounting', 'superadmin');
    }

    /**
     * Determine whether the user can view journal entries.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a journal entry.
     */
    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create journal entries.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update a journal entry.
     */
    public function update(User $user, JournalEntry $journalEntry): bool
    {
        return $journalEntry->isDraft();
    }

    /**
     * Journal entries must not be deleted.
     */
    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can post a journal entry.
     */
    public function post(User $user, JournalEntry $journalEntry): bool
    {
        return $this->canManageAccounting($user)
            && $journalEntry->isDraft();
    }

    /**
     * Determine whether the user can reverse a journal entry.
     */
    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        return $this->canManageAccounting($user)
            && $journalEntry->isPosted()
            && $journalEntry->reversal_of_id === null;
    }
}
