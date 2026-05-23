<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Congratulations — You've Been Admitted to {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#059669,#10b981); padding:36px 40px; text-align:center; }
  .header .emoji { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:24px; font-weight:700; }
  .header p  { color:#d1fae5; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .admit-box { background:#f0fdf4; border:2px solid #86efac; border-radius:10px; padding:24px 28px; margin:24px 0; }
  .admit-box .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #dcfce7; font-size:14px; }
  .admit-box .row:last-child { border-bottom:none; }
  .admit-box .lbl { color:#166534; font-weight:500; }
  .admit-box .val { color:#14532d; font-weight:700; text-align:right; }
  .section-title { font-size:13px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:600; margin:24px 0 12px; }
  .checklist { background:#f8fafc; border-radius:10px; padding:16px 20px; margin:0 0 20px; }
  .checklist-item { display:flex; gap:10px; align-items:flex-start; padding:7px 0; border-bottom:1px solid #e2e8f0; font-size:14px; color:#475569; }
  .checklist-item:last-child { border-bottom:none; }
  .checklist-item .icon { font-size:15px; flex-shrink:0; margin-top:1px; }
  .required { color:#dc2626; font-size:11px; font-weight:600; text-transform:uppercase; margin-left:6px; }
  .optional { color:#64748b; font-size:11px; font-weight:600; text-transform:uppercase; margin-left:6px; }
  .btn-wrap { text-align:center; margin:28px 0; }
  .btn { display:inline-block; background:#059669; color:#fff; text-decoration:none; padding:14px 40px; border-radius:8px; font-size:16px; font-weight:700; letter-spacing:0.02em; }
  .deadline-box { background:#fef3c7; border-left:4px solid #f59e0b; border-radius:4px; padding:14px 18px; margin:20px 0; font-size:13px; color:#78350f; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="emoji">🎉</div>
    <h1>Congratulations!</h1>
    <p>You have been admitted to {{ $schoolName }}</p>
  </div>
  <div class="body">
    <p>Dear <strong>{{ $studentName }}</strong>,</p>
    <p>We are delighted to inform you that your application has been <strong>approved</strong> and you have been officially admitted to <strong>{{ $schoolName }}</strong>. Welcome to our school family!</p>

    {{-- Admission details --}}
    <div class="admit-box">
      <div class="row"><span class="lbl">Admission Number</span><span class="val">{{ $admissionNumber }}</span></div>
      <div class="row"><span class="lbl">Class</span><span class="val">{{ $className }}</span></div>
      @if($sectionName)
      <div class="row"><span class="lbl">Section</span><span class="val">{{ $sectionName }}</span></div>
      @endif
      @if($campusName)
      <div class="row"><span class="lbl">Campus</span><span class="val">{{ $campusName }}</span></div>
      @endif
      <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $academicYear }}</span></div>
    </div>

    {{-- Complete your profile --}}
    <p class="section-title">📋 Action Required — Complete Your Student Profile</p>
    <p>To complete your enrolment, please visit the school office or log in to the parent portal and provide the following information and documents.</p>

    <p class="section-title" style="color:#dc2626;">Required Information</p>
    <div class="checklist">
      <div class="checklist-item"><span class="icon">👤</span><span>Full legal name (as per birth certificate) <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📅</span><span>Date of birth (verified against original document) <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">🧬</span><span>Gender <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">🩸</span><span>Blood group <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">🛐</span><span>Religion <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">🌍</span><span>Nationality <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📍</span><span>Permanent address and city <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📞</span><span>Student and guardian phone numbers <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📧</span><span>Parent / guardian email address <span class="required">required</span></span></div>
    </div>

    <p class="section-title" style="color:#2563eb;">Guardian / Family Information</p>
    <div class="checklist">
      <div class="checklist-item"><span class="icon">👨</span><span>Father's full name, CNIC, occupation, and phone <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">👩</span><span>Mother's full name, CNIC, and phone <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">🏠</span><span>Home address (if different from student's) <span class="optional">optional</span></span></div>
      <div class="checklist-item"><span class="icon">🚨</span><span>Emergency contact name and phone <span class="required">required</span></span></div>
    </div>

    <p class="section-title" style="color:#7c3aed;">Documents to Submit</p>
    <div class="checklist">
      <div class="checklist-item"><span class="icon">📸</span><span>Recent passport-size photographs (4 copies) <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📄</span><span>Original + photocopy of Birth Certificate <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📄</span><span>Father/Guardian's CNIC copy <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📋</span><span>Previous school's result/report card (last two years) <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">📋</span><span>Transfer/Leaving Certificate from previous school <span class="required">required</span></span></div>
      <div class="checklist-item"><span class="icon">💉</span><span>Vaccination record / health card <span class="optional">optional</span></span></div>
      <div class="checklist-item"><span class="icon">📋</span><span>Domicile certificate <span class="optional">optional</span></span></div>
    </div>

    <div class="deadline-box">
      ⚠️ <strong>Please submit all required documents within 7 days</strong> of receiving this letter to secure your admission. Failure to do so may result in cancellation of your seat.
    </div>

    @if($portalUrl)
    <div class="btn-wrap">
      <a href="{{ $portalUrl }}" class="btn">Complete My Profile Online</a>
    </div>
    @endif

    <p>If you have any questions, please contact the school admissions office. We look forward to welcoming you.</p>
    <p>Warm regards,<br><strong>{{ $schoolName }}</strong><br>Admissions Office</p>
  </div>
  <div class="footer">
    <p>{{ $schoolName }} · Congratulations on your admission!</p>
    <p style="margin-top:6px;">This is an official admission letter. Please keep it for your records.</p>
  </div>
</div>
</body>
</html>
