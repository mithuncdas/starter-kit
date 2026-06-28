<?php

namespace App\Support\Audit;

/**
 * Subject placeholder for Chronicle `auth.login.failed` entries when the
 * attempted email doesn't match any known user. Chronicle requires every
 * entry to have a subject reference (type + id) — this lets us record the
 * attempt without pretending it targeted a real user row.
 *
 * The class name becomes the entry's `subject_type`; the email becomes
 * the `subject_id`. Filterable from the audit-log API the same as any
 * other subject pair.
 */
class AnonymousLoginAttempt
{
    public function __construct(public string $id) {}
}
