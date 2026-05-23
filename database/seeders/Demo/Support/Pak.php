<?php

declare(strict_types=1);

namespace Database\Seeders\Demo\Support;

use Illuminate\Support\Str;

/**
 * Static helpers for the AQM Public School demo seeder.
 *
 * Pakistani name pools, CNIC/B-form/phone generators, deterministic
 * password formula. All randomness goes through mt_rand() seeded once
 * at the top of DemoTenantSeeder so reseeds produce identical content.
 */
final class Pak
{
    public const MALE_FIRST_NAMES = [
        'Ahmad', 'Ali', 'Hassan', 'Hussain', 'Bilal', 'Usman', 'Zain',
        'Faisal', 'Asad', 'Imran', 'Adeel', 'Rehan', 'Saad', 'Owais',
        'Hamza', 'Talha', 'Salman', 'Junaid', 'Haris', 'Tariq', 'Khalid',
        'Naveed', 'Aamir', 'Fawad', 'Yasir', 'Adnan', 'Kamran', 'Shahbaz',
        'Wajid', 'Anwar', 'Saif', 'Asif', 'Babar', 'Daniyal', 'Faraz',
        'Ibrahim', 'Sami', 'Arsalan', 'Waqar', 'Mubashir',
    ];

    public const FEMALE_FIRST_NAMES = [
        'Ayesha', 'Fatima', 'Maryam', 'Zainab', 'Hira', 'Sana', 'Aisha',
        'Maira', 'Aqsa', 'Sadia', 'Sumaira', 'Iqra', 'Areeba', 'Mahnoor',
        'Khadija', 'Komal', 'Sehrish', 'Nadia', 'Anum', 'Saba', 'Rabia',
        'Amna', 'Hina', 'Zoya', 'Zara', 'Bushra', 'Kiran', 'Mehwish',
        'Sidra', 'Tehmina', 'Tooba', 'Yusra', 'Aleena', 'Noor', 'Esha',
        'Eman', 'Mahira', 'Laiba', 'Rida', 'Sara',
    ];

    public const SURNAMES = [
        'Khan', 'Ahmed', 'Malik', 'Sheikh', 'Hussain', 'Ali', 'Raza',
        'Hassan', 'Shah', 'Mahmood', 'Iqbal', 'Aslam', 'Akram', 'Rashid',
        'Saeed', 'Ibrahim', 'Yousaf', 'Farooq', 'Akhtar', 'Siddiqui',
        'Qureshi', 'Awan', 'Cheema', 'Bhatti', 'Chaudhry', 'Khokhar',
        'Mirza', 'Ansari', 'Aziz', 'Naseem', 'Riaz', 'Tariq',
    ];

    public const CITIES = [
        ['name' => 'Lahore', 'province' => 'Punjab', 'postal' => '54000'],
        ['name' => 'Karachi', 'province' => 'Sindh', 'postal' => '74000'],
        ['name' => 'Islamabad', 'province' => 'Federal', 'postal' => '44000'],
        ['name' => 'Rawalpindi', 'province' => 'Punjab', 'postal' => '46000'],
        ['name' => 'Faisalabad', 'province' => 'Punjab', 'postal' => '38000'],
    ];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    public const BLOOD_GROUP_WEIGHTS = [
        'A+' => 26, 'A-' => 4, 'B+' => 30, 'B-' => 4,
        'O+' => 25, 'O-' => 3, 'AB+' => 6, 'AB-' => 2,
    ];

    public const PARENT_OCCUPATIONS = [
        'Engineer', 'Doctor', 'Teacher', 'Shopkeeper', 'Government Employee',
        'Businessman', 'Farmer', 'Bank Officer', 'Accountant', 'Lawyer',
        'Software Developer', 'Police Officer', 'Mechanic', 'Tailor', 'Electrician',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 30,
        'bank_transfer' => 35,
        'easypaisa' => 15,
        'jazzcash' => 15,
        'cheque' => 5,
    ];

    /**
     * Pick one element with a uniform random index. Uses mt_rand() so
     * the run is reproducible after mt_srand() is set.
     *
     * @template T
     * @param  array<int,T>  $list
     * @return T
     */
    public static function pick(array $list)
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    /**
     * Weighted pick. $weights is name => weight.
     *
     * @param  array<string,int>  $weights
     */
    public static function weightedPick(array $weights): string
    {
        $total = array_sum($weights);
        $r = mt_rand(1, $total);
        $cum = 0;
        foreach ($weights as $key => $weight) {
            $cum += $weight;
            if ($r <= $cum) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    /**
     * Synthetic CNIC: XXXXX-XXXXXXX-X (13 digits with dashes).
     */
    public static function cnic(): string
    {
        return sprintf(
            '%05d-%07d-%d',
            mt_rand(11000, 89999),
            mt_rand(1000000, 9999999),
            mt_rand(0, 9),
        );
    }

    /**
     * Synthetic Pakistani B-form (child national ID): same 13-digit format.
     */
    public static function bForm(): string
    {
        return self::cnic();
    }

    /**
     * Synthetic Pakistani mobile: +923XXXXXXXXX.
     */
    public static function phone(): string
    {
        return '+923' . mt_rand(0, 4) . mt_rand(10000000, 99999999);
    }

    /**
     * Build a city/address line: "House 12, Street 4, <Area>, <City>".
     *
     * @return array{address:string,city:string}
     */
    public static function address(?string $cityName = null): array
    {
        $city = $cityName ?: self::pick(self::CITIES)['name'];
        $areas = [
            'Lahore' => ['Johar Town', 'DHA Phase 5', 'Gulberg III', 'Model Town', 'Cantt'],
            'Karachi' => ['Clifton', 'DHA Phase 6', 'Gulshan-e-Iqbal', 'North Nazimabad', 'Bahadurabad'],
            'Islamabad' => ['F-7', 'F-8', 'G-9', 'I-8', 'E-11'],
            'Rawalpindi' => ['Saddar', 'Bahria Town', 'Westridge', 'Satellite Town', 'Chaklala Scheme III'],
            'Faisalabad' => ['Madina Town', 'Jinnah Colony', 'Gulistan Colony', 'D-Ground', 'Peoples Colony'],
        ];
        $area = self::pick($areas[$city] ?? $areas['Lahore']);

        return [
            'address' => sprintf(
                'House %d, Street %d, %s, %s',
                mt_rand(1, 250),
                mt_rand(1, 30),
                $area,
                $city,
            ),
            'city' => $city,
        ];
    }

    /**
     * Slugify a person's name into an email handle. Keeps it deterministic.
     */
    public static function emailHandle(string $first, string $last): string
    {
        $base = Str::lower(Str::ascii($first . '.' . $last));
        return preg_replace('/[^a-z0-9.]/', '', $base) ?: 'user';
    }

    /**
     * Email address using the AQM domain. If the handle collides with an
     * already-issued address, suffix a counter.
     *
     * @param  array<string,bool>  &$issued
     */
    public static function uniqueEmail(string $handle, array &$issued, string $domain = 'aqmdigital.com'): string
    {
        $candidate = $handle . '@' . $domain;
        $i = 1;
        while (isset($issued[$candidate])) {
            $i++;
            $candidate = $handle . $i . '@' . $domain;
        }
        $issued[$candidate] = true;
        return $candidate;
    }

    /**
     * Deterministic demo password formula from the spec:
     *   Demo2026@<first 6 chars of sha1($roleKey . $login . APP_KEY)>
     *
     * $roleKey is one of: admin, principal, vice-principal, accountant,
     * teacher, staff, parent, student. $login is the email (or
     * admission_number for students — caller decides what to feed).
     */
    public static function demoPassword(string $roleKey, string $login, string $appKey): string
    {
        return 'Demo2026@' . substr(sha1($roleKey . $login . $appKey), 0, 6);
    }

    /**
     * Map an auth-role-name to the password roleKey used in demoPassword().
     */
    public static function roleKeyFor(string $authRole): string
    {
        return match ($authRole) {
            'SCHOOL_ADMIN' => 'admin',
            'INSTITUTE_HEAD' => 'principal',
            'REGISTRAR' => 'vice-principal',
            'ACCOUNTANT' => 'accountant',
            'TEACHER' => 'teacher',
            'PARENT' => 'parent',
            'STUDENT' => 'student',
            default => 'staff',
        };
    }
}
