<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Complete Your Student Profile — {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#2563eb,#3b82f6); padding:36px 40px; text-align:center; }
  .header .emoji { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#bfdbfe; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .admit-box { background:#eff6ff; border:2px solid #bfdbfe; border-radius:10px; padding:20px 24px; margin:20px 0; }
  .admit-box .row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid #dbeafe; font-size:14px; }
  .admit-box .row:last-child { border-bottom:none; }
  .admit-box .lbl { color:#1e40af; font-weight:500; }
  .admit-box .val { color:#1e3a8a; font-weight:700; text-align:right; }
  .steps { margin:24px 0; }
  .step { display:flex; gap:14px; align-items:flex-start; padding:12px 0; border-bottom:1px solid #e2e8f0; }
  .step:last-child { border-bottom:none; }
  .step-num { width:28px; height:28px; border-radius:50%; background:#2563eb; color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
  .step-body { font-size:14px; color:#475569; line-height:1.5; }
  .step-body strong { color:#1e293b; }
  .btn-wrap { text-align:center; margin:32px 0 20px; }
  .btn { display:inline-block; background:#2563eb; color:#fff; text-decoration:none; padding:16px 44px; border-radius:10px; font-size:16px; font-weight:700; letter-spacing:0.02em; }
  .verify-note { background:#fefce8; border-left:4px solid #eab308; border-radius:4px; padding:14px 18px; margin:20px 0; font-size:13px; color:#713f12; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="emoji">📋</div>
    <h1>One More Step to Complete Your Admission</h1>
    <p>Please verify your email and fill in your full student profile</p>
  </div>
  <div class="body">
    <p>Dear <strong>{{ $studentName }}</strong>,</p>
    <p>Your admission to <strong>{{ $schoolName }}</strong> has been confirmed. To <strong>complete your enrolment</strong>, please click the button below to verify your email address and fill in the full student profile online.</p>

    <div class="admit-box">
      <div class="row"><span class="lbl">Admission Number</span><span class="val">{{ $admissionNumber }}</span></div>
      <div class="row"><span class="lbl">Class</span><span class="val">{{ $className }}{{ $sectionName ? ' — Section ' . $sectionName : '' }}</span></div>
      @if($campusName)
      <div class="row"><span class="lbl">Campus</span><span class="val">{{ $campusName }}</span></div>
      @endif
      <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $academicYear }}</span></div>
    </div>

    <p style="font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:8px;">What you'll fill in the form</p>
    <div class="steps">
      <div class="step">
        <div class="step-num">1</div>
        <div class="step-body"><strong>Personal details</strong> — blood group, religion, nationality, CNIC / B-Form number</div>
      </div>
      <div class="step">
        <div class="step-num">2</div>
        <div class="step-body"><strong>Father / guardian information</strong> — CNIC, occupation, phone, WhatsApp</div>
      </div>
      <div class="step">
        <div class="step-num">3</div>
        <div class="step-body"><strong>Mother / second guardian</strong> — name, CNIC, phone</div>
      </div>
      <div class="step">
        <div class="step-num">4</div>
        <div class="step-body"><strong>Emergency contact</strong> and <strong>medical notes</strong> (allergies, conditions)</div>
      </div>
    </div>

    <div class="verify-note">
      ⚠️ <strong>This link is unique to your admission.</strong> Do not share it. Once you click "Complete My Profile", your email address will be verified automatically.
    </div>

    <div class="btn-wrap">
      <a href="{{ $completeUrl }}" class="btn">✅ Complete My Profile</a>
    </div>

    <p style="font-size:13px;color:#94a3b8;text-align:center;">Or paste this link in your browser:<br>
      <span style="color:#2563eb;word-break:break-all;">{{ $completeUrl }}</span>
    </p>

    <p>If you have any questions, please contact the admissions office at <strong>{{ $schoolName }}</strong>. We look forward to welcoming you.</p>
    <p>Warm regards,<br><strong>{{ $schoolName }}</strong><br>Admissions Office</p>
  </div>
  <div class="footer">
    <p>{{ $schoolName }} · Admission Confirmation</p>
    <p style="margin-top:6px;">This link expires when your profile is completed. Keep it safe until then.</p>
  </div>
</div>
</body>
</html>
