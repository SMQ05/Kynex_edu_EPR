<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Models\Tenant\CertificateTemplate;
use App\Models\Tenant\IdCardTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds ready-to-edit HTML templates so admins have a working starting
 * point for certificates and ID cards instead of a blank textarea. Run
 * inside tenancy context. Idempotent: skips templates whose names already
 * exist in the tenant DB.
 */
class DefaultCertificateAndIdCardTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->certificateTemplates() as $template) {
            CertificateTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template,
            );
        }

        foreach ($this->idCardTemplates() as $template) {
            IdCardTemplate::firstOrCreate(
                ['name' => $template['name']],
                $template,
            );
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function certificateTemplates(): array
    {
        return [
            [
                'name'          => 'Classic Leaving Certificate',
                'template_type' => 'leaving',
                'is_active'     => true,
                'html_template' => $this->classicLeavingCertificate(),
                'variables'     => [
                    'student_name', 'first_name', 'last_name', 'admission_number',
                    'class_name', 'section_name', 'academic_year', 'father_name',
                    'mother_name', 'date_of_birth', 'certificate_number',
                    'issued_date', 'school_name', 'current_date',
                ],
            ],
            [
                'name'          => 'Character Certificate',
                'template_type' => 'character',
                'is_active'     => true,
                'html_template' => $this->characterCertificate(),
                'variables'     => [
                    'student_name', 'admission_number', 'class_name',
                    'father_name', 'date_of_birth', 'certificate_number',
                    'issued_date', 'school_name',
                ],
            ],
            [
                'name'          => 'Course Completion Certificate',
                'template_type' => 'completion',
                'is_active'     => true,
                'html_template' => $this->completionCertificate(),
                'variables'     => [
                    'student_name', 'admission_number', 'class_name',
                    'academic_year', 'certificate_number', 'issued_date',
                    'school_name',
                ],
            ],
            [
                'name'          => 'Achievement Award',
                'template_type' => 'achievement',
                'is_active'     => true,
                'html_template' => $this->achievementCertificate(),
                'variables'     => [
                    'student_name', 'class_name', 'academic_year',
                    'certificate_number', 'issued_date', 'school_name',
                ],
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function idCardTemplates(): array
    {
        return [
            [
                'name'          => 'Modern Student ID Card',
                'card_type'     => 'student',
                'is_active'     => true,
                'html_template' => $this->modernStudentIdCard(),
            ],
            [
                'name'          => 'Classic Student ID Card',
                'card_type'     => 'student',
                'is_active'     => true,
                'html_template' => $this->classicStudentIdCard(),
            ],
            [
                'name'          => 'Modern Staff ID Card',
                'card_type'     => 'staff',
                'is_active'     => true,
                'html_template' => $this->modernStaffIdCard(),
            ],
        ];
    }

    private function classicLeavingCertificate(): string
    {
        return <<<'HTML'
<div style="font-family: 'Georgia', serif; width: 800px; padding: 60px; border: 8px double #1e3a8a; background: #fff; color: #111;">
    <div style="text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 20px; margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 32px; color: #1e3a8a; letter-spacing: 2px;">{{school_name}}</h1>
        <p style="margin: 6px 0 0; font-size: 14px; color: #555;">SCHOOL LEAVING CERTIFICATE</p>
    </div>

    <p style="text-align: right; font-size: 14px; color: #555;">
        Certificate No: <strong>{{certificate_number}}</strong><br>
        Date of Issue: <strong>{{issued_date}}</strong>
    </p>

    <p style="font-size: 16px; line-height: 1.9; margin-top: 30px;">
        This is to certify that <strong style="font-size: 18px;">{{student_name}}</strong>,
        son/daughter of <strong>{{father_name}}</strong> and <strong>{{mother_name}}</strong>,
        was a bona fide student of this institution. He/She was admitted on the rolls under
        admission number <strong>{{admission_number}}</strong> and was studying in
        Class <strong>{{class_name}}</strong>, Section <strong>{{section_name}}</strong>
        during the academic year <strong>{{academic_year}}</strong>.
    </p>

    <p style="font-size: 16px; line-height: 1.9;">
        His/Her date of birth, as per school records, is <strong>{{date_of_birth}}</strong>.
        His/Her conduct and character during the period of study were satisfactory.
    </p>

    <p style="font-size: 16px; line-height: 1.9; margin-top: 24px;">
        We wish him/her every success in future endeavours.
    </p>

    <div style="display: flex; justify-content: space-between; margin-top: 80px;">
        <div style="text-align: center; width: 220px;">
            <div style="border-top: 1px solid #111; padding-top: 6px; font-size: 13px;">Class Teacher</div>
        </div>
        <div style="text-align: center; width: 220px;">
            <div style="border-top: 1px solid #111; padding-top: 6px; font-size: 13px;">Principal</div>
        </div>
    </div>
</div>
HTML;
    }

    private function characterCertificate(): string
    {
        return <<<'HTML'
<div style="font-family: 'Times New Roman', serif; width: 800px; padding: 60px; border: 6px solid #064e3b; background: #f0fdf4; color: #111;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 30px; color: #064e3b;">{{school_name}}</h1>
        <p style="margin: 8px 0 0; font-size: 18px; color: #065f46; letter-spacing: 4px;">CHARACTER CERTIFICATE</p>
    </div>

    <p style="text-align: right; font-size: 14px;">
        Ref: <strong>{{certificate_number}}</strong> &nbsp;&nbsp; Date: <strong>{{issued_date}}</strong>
    </p>

    <p style="font-size: 17px; line-height: 2; margin-top: 30px;">
        This is to certify that <strong>{{student_name}}</strong>, son/daughter of
        <strong>{{father_name}}</strong>, bearing admission number
        <strong>{{admission_number}}</strong> and date of birth <strong>{{date_of_birth}}</strong>,
        has been a student of <strong>Class {{class_name}}</strong> at this institution.
    </p>

    <p style="font-size: 17px; line-height: 2;">
        During his/her stay at the school, his/her character and conduct were found to be
        <strong>good</strong>. He/She is a hardworking and well-behaved student.
    </p>

    <p style="font-size: 17px; line-height: 2;">
        We wish him/her all the best for future endeavours.
    </p>

    <div style="margin-top: 100px; text-align: right;">
        <div style="display: inline-block; text-align: center; width: 240px;">
            <div style="border-top: 1px solid #111; padding-top: 6px; font-size: 14px;">Principal's Signature &amp; Seal</div>
        </div>
    </div>
</div>
HTML;
    }

    private function completionCertificate(): string
    {
        return <<<'HTML'
<div style="font-family: 'Helvetica', Arial, sans-serif; width: 800px; padding: 50px; background: linear-gradient(135deg,#fff7ed 0%,#fff 100%); border: 4px solid #c2410c; color: #111;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 28px; color: #c2410c;">{{school_name}}</h1>
        <h2 style="margin: 14px 0 0; font-size: 22px; color: #7c2d12; letter-spacing: 3px;">CERTIFICATE OF COMPLETION</h2>
    </div>

    <p style="text-align: center; font-size: 17px; color: #444; margin-top: 30px;">
        This certificate is proudly presented to
    </p>

    <h2 style="text-align: center; font-size: 38px; margin: 18px 0; color: #1e3a8a; font-family: 'Georgia', serif; font-style: italic;">
        {{student_name}}
    </h2>

    <p style="text-align: center; font-size: 16px; line-height: 1.8;">
        for successfully completing <strong>Class {{class_name}}</strong><br>
        during the academic year <strong>{{academic_year}}</strong>.<br>
        Admission No: <strong>{{admission_number}}</strong>
    </p>

    <div style="display: flex; justify-content: space-between; margin-top: 80px; font-size: 13px;">
        <div>Certificate No: <strong>{{certificate_number}}</strong></div>
        <div>Issued: <strong>{{issued_date}}</strong></div>
    </div>

    <div style="display: flex; justify-content: space-around; margin-top: 60px;">
        <div style="text-align: center; width: 220px;">
            <div style="border-top: 1px solid #111; padding-top: 6px; font-size: 13px;">Class Teacher</div>
        </div>
        <div style="text-align: center; width: 220px;">
            <div style="border-top: 1px solid #111; padding-top: 6px; font-size: 13px;">Principal</div>
        </div>
    </div>
</div>
HTML;
    }

    private function achievementCertificate(): string
    {
        return <<<'HTML'
<div style="font-family: 'Garamond', serif; width: 800px; padding: 60px; background: #fffbeb; border: 10px solid #b45309; color: #111; position: relative;">
    <div style="position: absolute; top: 20px; right: 20px; font-size: 64px; color: #fbbf24;">&#9733;</div>
    <div style="position: absolute; top: 20px; left: 20px; font-size: 64px; color: #fbbf24;">&#9733;</div>

    <div style="text-align: center; margin-top: 40px;">
        <h1 style="margin: 0; font-size: 30px; color: #78350f;">{{school_name}}</h1>
        <h2 style="margin: 18px 0 6px; font-size: 36px; color: #b45309; letter-spacing: 6px;">ACHIEVEMENT</h2>
        <p style="margin: 0; font-size: 14px; color: #92400e;">presented to</p>
    </div>

    <h2 style="text-align: center; font-size: 44px; margin: 30px 0 10px; color: #1e3a8a; font-style: italic;">
        {{student_name}}
    </h2>

    <p style="text-align: center; font-size: 17px; line-height: 1.9; margin-top: 20px;">
        Class <strong>{{class_name}}</strong> &middot; Academic Year <strong>{{academic_year}}</strong><br>
        in recognition of outstanding academic performance and exemplary contribution.
    </p>

    <div style="display: flex; justify-content: space-between; margin-top: 100px; font-size: 13px;">
        <div>
            <div style="border-top: 1px solid #111; padding-top: 6px; width: 220px; text-align: center;">Principal</div>
        </div>
        <div style="text-align: right;">
            Cert. No: <strong>{{certificate_number}}</strong><br>
            Date: <strong>{{issued_date}}</strong>
        </div>
    </div>
</div>
HTML;
    }

    private function modernStudentIdCard(): string
    {
        return <<<'HTML'
<div style="width: 320px; height: 510px; font-family: 'Helvetica', Arial, sans-serif; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(0,0,0,0.12); color: #fff; background: linear-gradient(160deg,#1e3a8a 0%,#3b82f6 100%);">
    <div style="padding: 18px 18px 12px; text-align: center; background: rgba(0,0,0,0.18);">
        <div style="font-size: 16px; font-weight: 700; letter-spacing: 1px;">{{school_name}}</div>
        <div style="font-size: 11px; opacity: 0.85; margin-top: 2px;">STUDENT IDENTITY CARD</div>
    </div>

    <div style="display: flex; justify-content: center; margin-top: 20px;">
        <img src="{{photo_url}}" alt="" style="width: 110px; height: 130px; object-fit: cover; border-radius: 8px; border: 3px solid #fff;">
    </div>

    <div style="padding: 18px 22px 8px; text-align: center;">
        <div style="font-size: 18px; font-weight: 700;">{{student_name}}</div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">Adm. No: {{admission_number}}</div>
    </div>

    <div style="padding: 4px 22px 12px; font-size: 12px; line-height: 1.7;">
        <div style="display: flex; justify-content: space-between;"><span style="opacity: 0.8;">Class</span><strong>{{class_name}} - {{section_name}}</strong></div>
        <div style="display: flex; justify-content: space-between;"><span style="opacity: 0.8;">Blood Group</span><strong>{{blood_group}}</strong></div>
        <div style="display: flex; justify-content: space-between;"><span style="opacity: 0.8;">Father</span><strong>{{father_name}}</strong></div>
    </div>

    <div style="margin-top: auto; padding: 10px 22px; background: rgba(0,0,0,0.18); font-size: 10px; text-align: center; opacity: 0.9;">
        {{address}}
    </div>
</div>
HTML;
    }

    private function classicStudentIdCard(): string
    {
        return <<<'HTML'
<div style="width: 320px; height: 510px; font-family: 'Arial', sans-serif; border: 2px solid #111; background: #fff; color: #111;">
    <div style="background: #064e3b; color: #fff; padding: 12px 16px; text-align: center;">
        <div style="font-size: 16px; font-weight: 700;">{{school_name}}</div>
        <div style="font-size: 10px; letter-spacing: 2px; opacity: 0.85; margin-top: 2px;">STUDENT ID CARD</div>
    </div>

    <div style="display: flex; justify-content: center; margin-top: 16px;">
        <img src="{{photo_url}}" alt="" style="width: 120px; height: 140px; object-fit: cover; border: 2px solid #064e3b;">
    </div>

    <div style="padding: 14px 18px 4px; text-align: center;">
        <div style="font-size: 18px; font-weight: 700; color: #064e3b;">{{student_name}}</div>
    </div>

    <div style="padding: 6px 18px; font-size: 12px; line-height: 1.8;">
        <div><strong>Adm No:</strong> {{admission_number}}</div>
        <div><strong>Class:</strong> {{class_name}} &mdash; {{section_name}}</div>
        <div><strong>Blood Group:</strong> {{blood_group}}</div>
        <div><strong>Father:</strong> {{father_name}}</div>
        <div><strong>Address:</strong> {{address}}</div>
    </div>

    <div style="border-top: 1px dashed #999; margin: 12px 16px 8px; padding-top: 8px; font-size: 10px; text-align: center;">
        If found, please return to {{school_name}}.
    </div>
</div>
HTML;
    }

    private function modernStaffIdCard(): string
    {
        return <<<'HTML'
<div style="width: 320px; height: 510px; font-family: 'Helvetica', Arial, sans-serif; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(0,0,0,0.12); color: #fff; background: linear-gradient(160deg,#7c2d12 0%,#ea580c 100%);">
    <div style="padding: 18px; text-align: center; background: rgba(0,0,0,0.18);">
        <div style="font-size: 16px; font-weight: 700; letter-spacing: 1px;">{{school_name}}</div>
        <div style="font-size: 11px; opacity: 0.85; margin-top: 2px;">STAFF IDENTITY CARD</div>
    </div>

    <div style="display: flex; justify-content: center; margin-top: 22px;">
        <img src="{{photo_url}}" alt="" style="width: 110px; height: 130px; object-fit: cover; border-radius: 8px; border: 3px solid #fff;">
    </div>

    <div style="padding: 18px 22px 8px; text-align: center;">
        <div style="font-size: 18px; font-weight: 700;">{{full_name}}</div>
        <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;">{{designation}}</div>
    </div>

    <div style="padding: 4px 22px 12px; font-size: 12px; line-height: 1.7;">
        <div style="display: flex; justify-content: space-between;"><span style="opacity: 0.8;">Employee ID</span><strong>{{employee_id}}</strong></div>
        <div style="display: flex; justify-content: space-between;"><span style="opacity: 0.8;">Department</span><strong>{{department}}</strong></div>
    </div>

    <div style="margin-top: 80px; padding: 10px 22px; background: rgba(0,0,0,0.18); font-size: 10px; text-align: center; opacity: 0.9;">
        Authorised personnel of {{school_name}}.
    </div>
</div>
HTML;
    }
}
