<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Models\Tenant\CertificateTemplate;
use App\Models\Tenant\IdCardTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds dompdf-friendly HTML templates for certificates and ID cards.
 *
 * Re-runnable: each row is upserted by name, so an existing tenant whose
 * templates were seeded with an older HTML body can pick up new rendering
 * fixes by re-running this seeder.
 *
 * Layout rules baked in here (driven by dompdf's quirks):
 *  - No CSS flexbox or grid — uses tables for column layouts
 *  - No CSS gradients (replaced with solid background-color)
 *  - Explicit width/height in pt or px (no rem/em)
 *  - DejaVu Sans for diacritics
 *  - {{verification_qr}} expects a base64 data URI from CertificateService
 */
class DefaultCertificateAndIdCardTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->certificateTemplates() as $template) {
            CertificateTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template,
            );
        }

        foreach ($this->idCardTemplates() as $template) {
            IdCardTemplate::updateOrCreate(
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
                    'registration_number', 'class_name', 'section_name',
                    'academic_year', 'father_name', 'mother_name',
                    'date_of_birth', 'certificate_number',
                    'issued_date', 'school_name', 'current_date',
                    'verification_qr', 'verification_url',
                ],
            ],
            [
                'name'          => 'Character Certificate',
                'template_type' => 'character',
                'is_active'     => true,
                'html_template' => $this->characterCertificate(),
                'variables'     => [
                    'student_name', 'admission_number', 'registration_number',
                    'class_name', 'father_name', 'date_of_birth',
                    'certificate_number', 'issued_date', 'school_name',
                    'verification_qr', 'verification_url',
                ],
            ],
            [
                'name'          => 'Course Completion Certificate',
                'template_type' => 'completion',
                'is_active'     => true,
                'html_template' => $this->completionCertificate(),
                'variables'     => [
                    'student_name', 'admission_number', 'registration_number',
                    'class_name', 'academic_year', 'certificate_number',
                    'issued_date', 'school_name',
                    'verification_qr', 'verification_url',
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
                    'registration_number', 'verification_qr', 'verification_url',
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
<table style="width:100%;border-collapse:collapse;font-family:DejaVu Sans,Georgia,serif;color:#111;">
  <tr><td style="padding:24px;border:6px double #1e3a8a;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="text-align:center;border-bottom:2px solid #1e3a8a;padding-bottom:12px;">
        <div style="font-size:26px;font-weight:bold;color:#1e3a8a;letter-spacing:2px;">{{school_name}}</div>
        <div style="font-size:12px;color:#444;margin-top:4px;letter-spacing:6px;">SCHOOL LEAVING CERTIFICATE</div>
      </td></tr>
      <tr><td style="padding-top:14px;text-align:right;font-size:12px;color:#444;">
        Certificate No: <strong>{{certificate_number}}</strong> &nbsp;|&nbsp;
        Issued: <strong>{{issued_date}}</strong>
      </td></tr>
      <tr><td style="font-size:14px;line-height:1.7;padding-top:18px;">
        This is to certify that <strong style="font-size:16px;">{{student_name}}</strong>,
        son/daughter of <strong>{{father_name}}</strong> and <strong>{{mother_name}}</strong>,
        was a bona fide student of this institution. He/She was admitted under
        admission number <strong>{{admission_number}}</strong>
        (Registration <strong>{{registration_number}}</strong>) and was studying in
        Class <strong>{{class_name}}</strong>, Section <strong>{{section_name}}</strong>
        during the academic year <strong>{{academic_year}}</strong>.
      </td></tr>
      <tr><td style="font-size:14px;line-height:1.7;padding-top:8px;">
        His/Her date of birth, as per school records, is <strong>{{date_of_birth}}</strong>.
        Conduct and character during the period of study were satisfactory.
      </td></tr>
      <tr><td style="font-size:14px;line-height:1.7;padding-top:14px;">
        We wish him/her every success in future endeavours.
      </td></tr>
      <tr><td style="padding-top:60px;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="width:50%;text-align:center;font-size:12px;border-top:1px solid #111;padding-top:6px;">Class Teacher</td>
            <td style="width:50%;text-align:center;font-size:12px;border-top:1px solid #111;padding-top:6px;">Principal</td>
          </tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    private function characterCertificate(): string
    {
        return <<<'HTML'
<table style="width:100%;border-collapse:collapse;font-family:DejaVu Sans,'Times New Roman',serif;color:#111;">
  <tr><td style="padding:24px;border:5px solid #064e3b;background:#f0fdf4;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="text-align:center;">
        <div style="font-size:24px;font-weight:bold;color:#064e3b;">{{school_name}}</div>
        <div style="font-size:14px;color:#065f46;letter-spacing:4px;margin-top:6px;">CHARACTER CERTIFICATE</div>
      </td></tr>
      <tr><td style="text-align:right;font-size:12px;padding-top:14px;">
        Ref: <strong>{{certificate_number}}</strong> &nbsp;|&nbsp;
        Date: <strong>{{issued_date}}</strong>
      </td></tr>
      <tr><td style="font-size:15px;line-height:1.85;padding-top:18px;">
        This is to certify that <strong>{{student_name}}</strong>, son/daughter of
        <strong>{{father_name}}</strong>, bearing admission number
        <strong>{{admission_number}}</strong> (Registration <strong>{{registration_number}}</strong>)
        and date of birth <strong>{{date_of_birth}}</strong>,
        has been a student of <strong>Class {{class_name}}</strong> at this institution.
      </td></tr>
      <tr><td style="font-size:15px;line-height:1.85;padding-top:8px;">
        During his/her stay at the school, his/her character and conduct were found to be
        <strong>good</strong>. He/She is a hardworking and well-behaved student.
      </td></tr>
      <tr><td style="font-size:15px;line-height:1.85;padding-top:8px;">
        We wish him/her all the best for future endeavours.
      </td></tr>
      <tr><td style="padding-top:60px;text-align:right;font-size:12px;">
        <div style="display:inline-block;border-top:1px solid #111;padding-top:6px;width:200px;text-align:center;">
          Principal's Signature &amp; Seal
        </div>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    private function completionCertificate(): string
    {
        return <<<'HTML'
<table style="width:100%;border-collapse:collapse;font-family:DejaVu Sans,Helvetica,Arial,sans-serif;color:#111;">
  <tr><td style="padding:24px;border:4px solid #c2410c;background:#fff7ed;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="text-align:center;">
        <div style="font-size:22px;font-weight:bold;color:#c2410c;">{{school_name}}</div>
        <div style="font-size:18px;color:#7c2d12;letter-spacing:3px;margin-top:8px;">CERTIFICATE OF COMPLETION</div>
      </td></tr>
      <tr><td style="text-align:center;font-size:14px;padding-top:18px;color:#444;">
        This certificate is proudly presented to
      </td></tr>
      <tr><td style="text-align:center;font-size:30px;color:#1e3a8a;font-style:italic;padding-top:6px;font-family:DejaVu Sans,Georgia,serif;">
        {{student_name}}
      </td></tr>
      <tr><td style="text-align:center;font-size:14px;line-height:1.7;padding-top:8px;">
        for successfully completing <strong>Class {{class_name}}</strong><br>
        during the academic year <strong>{{academic_year}}</strong>.<br>
        Admission No: <strong>{{admission_number}}</strong> &nbsp;|&nbsp; Registration: <strong>{{registration_number}}</strong>
      </td></tr>
      <tr><td style="padding-top:50px;font-size:12px;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="width:50%;text-align:left;">Certificate No: <strong>{{certificate_number}}</strong></td>
            <td style="width:50%;text-align:right;">Issued: <strong>{{issued_date}}</strong></td>
          </tr>
        </table>
      </td></tr>
      <tr><td style="padding-top:40px;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="width:50%;text-align:center;font-size:12px;border-top:1px solid #111;padding-top:6px;">Class Teacher</td>
            <td style="width:50%;text-align:center;font-size:12px;border-top:1px solid #111;padding-top:6px;">Principal</td>
          </tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    private function achievementCertificate(): string
    {
        return <<<'HTML'
<table style="width:100%;border-collapse:collapse;font-family:DejaVu Sans,Garamond,serif;color:#111;">
  <tr><td style="padding:30px;border:8px solid #b45309;background:#fffbeb;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="text-align:center;">
        <div style="font-size:24px;font-weight:bold;color:#78350f;">{{school_name}}</div>
        <div style="font-size:32px;font-weight:bold;color:#b45309;letter-spacing:6px;margin-top:14px;">ACHIEVEMENT</div>
        <div style="font-size:12px;color:#92400e;margin-top:4px;">presented to</div>
      </td></tr>
      <tr><td style="text-align:center;font-size:36px;color:#1e3a8a;font-style:italic;padding-top:18px;">
        {{student_name}}
      </td></tr>
      <tr><td style="text-align:center;font-size:14px;line-height:1.7;padding-top:14px;">
        Class <strong>{{class_name}}</strong> &middot; Academic Year <strong>{{academic_year}}</strong><br>
        in recognition of outstanding academic performance and exemplary contribution.
      </td></tr>
      <tr><td style="padding-top:60px;font-size:12px;">
        <table style="width:100%;border-collapse:collapse;">
          <tr>
            <td style="width:50%;border-top:1px solid #111;padding-top:6px;text-align:center;">Principal</td>
            <td style="width:50%;text-align:right;">
              Cert. No: <strong>{{certificate_number}}</strong><br>
              Date: <strong>{{issued_date}}</strong>
            </td>
          </tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    private function modernStudentIdCard(): string
    {
        return <<<'HTML'
<!-- ╔══ FRONT ══╗ -->
<table style="width:100%;border-collapse:collapse;background:#1e3a8a;font-family:DejaVu Sans,Helvetica,Arial,sans-serif;color:#fff;">
  <tr><td style="padding:0;">
    <table style="width:100%;border-collapse:collapse;"><tr><td style="background:#3b82f6;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr></table>
    <table style="width:100%;border-collapse:collapse;background:#1e3a8a;"><tr><td style="padding:10px 12px 6px;text-align:center;color:#fff;">
      <div style="font-size:12px;font-weight:bold;letter-spacing:1.5px;">{{school_name}}</div>
      <div style="font-size:8px;letter-spacing:3px;color:#cbd5e1;margin-top:2px;">STUDENT IDENTITY CARD</div>
    </td></tr></table>
    <table style="width:90%;border-collapse:collapse;background:#ffffff;color:#0f172a;margin:8px auto;border:1px solid #cbd5e1;">
      <tr><td style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="text-align:center;padding:12px 0 4px;background:#f8fafc;"><img src="{{photo_url}}" alt="" style="width:96px;height:118px;border:3px solid #1e3a8a;background:#fff;"></td></tr>
          <tr><td style="text-align:center;padding:8px 8px 2px;">
            <div style="font-size:14px;font-weight:bold;color:#1e3a8a;letter-spacing:0.4px;">{{student_name}}</div>
            <div style="font-size:9px;color:#475569;margin-top:1px;">Adm: <strong>{{admission_number}}</strong> &middot; Reg: <strong>{{registration_number}}</strong></div>
          </td></tr>
          <tr><td style="padding:6px 14px 8px;font-size:10px;">
            <table style="width:100%;border-collapse:collapse;">
              <tr style="background:#eef2ff;"><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Class</td><td style="padding:3px 6px;text-align:right;">{{class_name}} &middot; {{section_name}}</td></tr>
              <tr><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Blood Group</td><td style="padding:3px 6px;text-align:right;">{{blood_group}}</td></tr>
              <tr style="background:#eef2ff;"><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Father</td><td style="padding:3px 6px;text-align:right;">{{father_name}}</td></tr>
              <tr><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Phone</td><td style="padding:3px 6px;text-align:right;">{{guardian_phone}}</td></tr>
            </table>
          </td></tr>
        </table>
      </td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#1e3a8a;"><tr><td style="padding:6px 10px;font-size:8px;text-align:center;color:#cbd5e1;">{{address}}</td></tr></table>
    <table style="width:100%;border-collapse:collapse;"><tr><td style="background:#3b82f6;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr></table>
  </td></tr>
</table>

<div style="page-break-after:always;"></div>

<!-- ╔══ BACK ══╗ -->
<table style="width:100%;border-collapse:collapse;background:#1e3a8a;font-family:DejaVu Sans,Helvetica,Arial,sans-serif;color:#fff;">
  <tr><td style="padding:0;">
    <table style="width:100%;border-collapse:collapse;"><tr><td style="background:#3b82f6;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr></table>
    <table style="width:100%;border-collapse:collapse;background:#1e3a8a;"><tr><td style="padding:10px 12px 6px;text-align:center;color:#fff;">
      <div style="font-size:12px;font-weight:bold;letter-spacing:1.5px;">{{school_name}}</div>
      <div style="font-size:8px;letter-spacing:3px;color:#cbd5e1;margin-top:2px;">CARD HOLDER &middot; VERIFICATION</div>
    </td></tr></table>
    <table style="width:90%;border-collapse:collapse;background:#ffffff;color:#0f172a;margin:8px auto;border:1px solid #cbd5e1;">
      <tr><td style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="text-align:center;padding:10px 0 4px;background:#f8fafc;">
            <img src="{{verification_qr}}" alt="QR" style="width:110px;height:110px;border:1px solid #1e3a8a;background:#fff;padding:4px;">
            <div style="font-size:8px;color:#475569;margin-top:4px;letter-spacing:0.5px;">SCAN TO VERIFY</div>
          </td></tr>
          <tr><td style="padding:6px 14px 8px;font-size:10px;">
            <table style="width:100%;border-collapse:collapse;">
              <tr style="background:#eef2ff;"><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Date of Birth</td><td style="padding:3px 6px;text-align:right;">{{date_of_birth}}</td></tr>
              <tr><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Religion</td><td style="padding:3px 6px;text-align:right;">{{religion}}</td></tr>
              <tr style="background:#eef2ff;"><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Mother</td><td style="padding:3px 6px;text-align:right;">{{mother_name}}</td></tr>
              <tr><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Year</td><td style="padding:3px 6px;text-align:right;">{{academic_year}}</td></tr>
              <tr style="background:#eef2ff;"><td style="padding:3px 6px;color:#1e3a8a;font-weight:bold;">Campus</td><td style="padding:3px 6px;text-align:right;">{{campus_name}}</td></tr>
            </table>
          </td></tr>
          <tr><td style="padding:6px 14px 10px;font-size:8px;color:#64748b;text-align:center;font-style:italic;border-top:1px dashed #cbd5e1;">
            If found, please return to {{school_name}} or call the number on the front.
          </td></tr>
        </table>
      </td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#1e3a8a;"><tr><td style="padding:6px 10px;font-size:7px;text-align:center;color:#cbd5e1;letter-spacing:0.5px;">
      Powered by <strong style="color:#fff;">kynexsolutions.com</strong>
    </td></tr></table>
    <table style="width:100%;border-collapse:collapse;"><tr><td style="background:#3b82f6;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr></table>
  </td></tr>
</table>
HTML;
    }

    private function classicStudentIdCard(): string
    {
        return <<<'HTML'
<!-- ╔══ FRONT ══╗ -->
<table style="width:100%;border-collapse:collapse;background:#064e3b;font-family:DejaVu Sans,Arial,sans-serif;color:#fff;">
  <tr><td style="padding:0;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#fbbf24;height:3px;font-size:0;line-height:0;">&nbsp;</td></tr>
      <tr><td style="background:#10b981;height:8px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#064e3b;"><tr><td style="padding:10px 12px 6px;text-align:center;color:#fff;">
      <div style="font-size:12px;font-weight:bold;letter-spacing:1px;">{{school_name}}</div>
      <div style="font-size:8px;letter-spacing:3px;color:#a7f3d0;margin-top:2px;">STUDENT ID CARD</div>
    </td></tr></table>
    <table style="width:90%;border-collapse:collapse;background:#fffbeb;color:#0f172a;margin:8px auto;border:2px solid #fbbf24;">
      <tr><td style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="text-align:center;padding:12px 0 4px;background:#fef3c7;"><img src="{{photo_url}}" alt="" style="width:96px;height:118px;border:3px solid #064e3b;background:#fff;"></td></tr>
          <tr><td style="text-align:center;padding:6px 8px 2px;"><div style="font-size:14px;font-weight:bold;color:#064e3b;">{{student_name}}</div></td></tr>
          <tr><td style="padding:6px 14px 8px;font-size:10px;line-height:1.7;color:#1f2937;">
            <strong style="color:#064e3b;">Adm No:</strong> {{admission_number}} &middot;
            <strong style="color:#064e3b;">Reg:</strong> {{registration_number}}<br>
            <strong style="color:#064e3b;">Class:</strong> {{class_name}} &mdash; {{section_name}}<br>
            <strong style="color:#064e3b;">Blood Group:</strong> {{blood_group}}<br>
            <strong style="color:#064e3b;">Father:</strong> {{father_name}}<br>
            <strong style="color:#064e3b;">Phone:</strong> {{guardian_phone}}
          </td></tr>
        </table>
      </td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#064e3b;"><tr><td style="padding:6px 10px;font-size:8px;text-align:center;color:#a7f3d0;font-style:italic;">If found, please return to {{school_name}}.</td></tr></table>
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#10b981;height:8px;font-size:0;line-height:0;">&nbsp;</td></tr>
      <tr><td style="background:#fbbf24;height:3px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
  </td></tr>
</table>

<div style="page-break-after:always;"></div>

<!-- ╔══ BACK ══╗ -->
<table style="width:100%;border-collapse:collapse;background:#064e3b;font-family:DejaVu Sans,Arial,sans-serif;color:#fff;">
  <tr><td style="padding:0;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#fbbf24;height:3px;font-size:0;line-height:0;">&nbsp;</td></tr>
      <tr><td style="background:#10b981;height:8px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#064e3b;"><tr><td style="padding:10px 12px 6px;text-align:center;color:#fff;">
      <div style="font-size:12px;font-weight:bold;letter-spacing:1px;">{{school_name}}</div>
      <div style="font-size:8px;letter-spacing:3px;color:#a7f3d0;margin-top:2px;">VERIFICATION</div>
    </td></tr></table>
    <table style="width:90%;border-collapse:collapse;background:#fffbeb;color:#0f172a;margin:8px auto;border:2px solid #fbbf24;">
      <tr><td style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="text-align:center;padding:10px 0 4px;background:#fef3c7;">
            <img src="{{verification_qr}}" alt="QR" style="width:110px;height:110px;border:1px solid #064e3b;background:#fff;padding:4px;">
            <div style="font-size:8px;color:#475569;margin-top:4px;letter-spacing:0.5px;">SCAN TO VERIFY</div>
          </td></tr>
          <tr><td style="padding:6px 14px 8px;font-size:10px;line-height:1.7;color:#1f2937;">
            <strong style="color:#064e3b;">DOB:</strong> {{date_of_birth}}<br>
            <strong style="color:#064e3b;">Religion:</strong> {{religion}}<br>
            <strong style="color:#064e3b;">Nationality:</strong> {{nationality}}<br>
            <strong style="color:#064e3b;">Mother:</strong> {{mother_name}}<br>
            <strong style="color:#064e3b;">Year:</strong> {{academic_year}} &middot;
            <strong style="color:#064e3b;">Campus:</strong> {{campus_name}}
          </td></tr>
          <tr><td style="padding:6px 14px 10px;font-size:8px;color:#475569;text-align:center;font-style:italic;border-top:1px dashed #fbbf24;">
            {{address}}
          </td></tr>
        </table>
      </td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#064e3b;"><tr><td style="padding:6px 10px;font-size:7px;text-align:center;color:#a7f3d0;letter-spacing:0.5px;">
      Powered by <strong style="color:#fff;">kynexsolutions.com</strong>
    </td></tr></table>
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#10b981;height:8px;font-size:0;line-height:0;">&nbsp;</td></tr>
      <tr><td style="background:#fbbf24;height:3px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }

    private function modernStaffIdCard(): string
    {
        return <<<'HTML'
<table style="width:100%;height:100%;border-collapse:collapse;background:#7c2d12;font-family:DejaVu Sans,Helvetica,Arial,sans-serif;color:#fff;">
  <tr><td style="padding:0;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#fbbf24;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;background:#7c2d12;">
      <tr><td style="padding:10px 12px 6px;text-align:center;color:#fff;">
        <div style="font-size:12px;font-weight:bold;letter-spacing:1.5px;">{{school_name}}</div>
        <div style="font-size:8px;letter-spacing:3px;color:#fed7aa;margin-top:2px;">STAFF IDENTITY CARD</div>
      </td></tr>
    </table>

    <table style="width:90%;border-collapse:collapse;background:#ffffff;color:#0f172a;margin:8px auto;border:1px solid #fdba74;">
      <tr><td style="padding:0;">
        <table style="width:100%;border-collapse:collapse;">
          <tr><td style="text-align:center;padding:12px 0 4px;background:#fff7ed;">
            <img src="{{photo_url}}" alt="" style="width:96px;height:118px;border:3px solid #7c2d12;background:#fff;">
          </td></tr>
          <tr><td style="text-align:center;padding:8px 8px 2px;">
            <div style="font-size:14px;font-weight:bold;color:#7c2d12;">{{full_name}}</div>
            <div style="font-size:10px;color:#475569;font-style:italic;">{{designation}}</div>
          </td></tr>
          <tr><td style="padding:6px 14px 8px;font-size:10px;">
            <table style="width:100%;border-collapse:collapse;">
              <tr style="background:#fff7ed;">
                <td style="padding:3px 6px;color:#7c2d12;font-weight:bold;">Employee ID</td>
                <td style="padding:3px 6px;text-align:right;">{{employee_id}}</td>
              </tr>
              <tr>
                <td style="padding:3px 6px;color:#7c2d12;font-weight:bold;">Department</td>
                <td style="padding:3px 6px;text-align:right;">{{department}}</td>
              </tr>
              <tr style="background:#fff7ed;">
                <td style="padding:3px 6px;color:#7c2d12;font-weight:bold;">Phone</td>
                <td style="padding:3px 6px;text-align:right;">{{phone}}</td>
              </tr>
            </table>
          </td></tr>
        </table>
      </td></tr>
    </table>

    <table style="width:100%;border-collapse:collapse;background:#7c2d12;">
      <tr><td style="padding:6px 10px;font-size:8px;text-align:center;color:#fed7aa;">
        Authorised personnel of {{school_name}}.
      </td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="background:#fbbf24;height:6px;font-size:0;line-height:0;">&nbsp;</td></tr>
    </table>
  </td></tr>
</table>
HTML;
    }
}
