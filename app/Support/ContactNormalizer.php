<?php

namespace App\Support;

/**
 * One canonical form for the contact details people type at checkout, so the same person is
 * recognised across orders (and is ready for email / SMS tools): lower-case emails and phone
 * numbers in international digits (Pakistani "0300 1234567" and "+92 300 1234567" both become 923001234567).
 */
class ContactNormalizer
{
    public static function email(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || strlen($digits) < 7) {
            return null;
        }

        // Pakistani mobile/landline written with a leading 0 → country code 92.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0') && strlen($digits) >= 10 && strlen($digits) <= 11) {
            $digits = '92'.substr($digits, 1);
        }

        return substr($digits, 0, 20);
    }
}
