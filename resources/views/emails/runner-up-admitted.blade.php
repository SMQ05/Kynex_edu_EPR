<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Great News — A Seat is Now Available for You | {{ $schoolName }}</title>
<style>
  body { margin:0; padding:0; background:#f1f5f9; font-family:'Segoe UI',Arial,sans-serif; color:#1e293b; }
  .wrapper { max-width:600px; margin:40px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.08); }
  .header { background:linear-gradient(135deg,#7c3aed,#8b5cf6); padding:36px 40px; text-align:center; }
  .header .emoji { font-size:40px; margin-bottom:10px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; font-weight:700; }
  .header p  { color:#ede9fe; margin:0; font-size:14px; }
  .body { padding:40px; }
  .body p { margin:0 0 16px; line-height:1.7; font-size:15px; color:#475569; }
  .body strong { color:#1e293b; }
  .admit-box { background:#f5f3ff; border:2px solid #c4b5fd; border-radius:10px; padding:20px 24px; margin:20px 0; }
  .admit-box .row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid #ddd6fe; font-size:14px; }
  .admit-box .row:last-child { border-bottom:none; }
  .admit-box .lbl { color:#5b21b6; font-weight:500; }
  .admit-box .val { color:#4c1d95; font-weight:700; text-align:right; }
  .highlight-box { background:#fefce8; border-left:4px solid #eab308; border-radius:4px; padding:14px 18px; margin:20px 0; font-size:14px; color:#713f12; line-height:1.6; }
  .btn-wrap { text-align:center; margin:32px 0 20px; }
  .btn { display:inline-block; background:#7c3aed; color:#fff; text-decoration:none; padding:16px 44px; border-radius:10px; font-size:16px; font-weight:700; letter-spacing:0.02em; }
  .footer { background:#f8fafc; border-top:1px solid #e2e8f0; padding:24px 40px; text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <div class="emoji">🎊</div>
    <h1>Great News — A Seat is Now Available!</h1>
    <p>You have been moved from the waitlist to admitted</p>
  </div>
  <div class="body">
    <p>Dear <strong>{{ $studentName }}</strong>,</p>
    <p>We are pleased to inform you that a seat has become available at <strong>{{ $schoolName }}</strong> and <strong>you have been selected from the waitlist for admission</strong>. Congratulations!</p>

    <div class="admit-box">
      <div class="row"><span class="lbl">Admission Number</span><span class="val">{{ $admissionNumber }}</span></div>
      <div class="row"><span class="lbl">Class</span><span class="val">{{ $className }}{{ $sectionName ? ' — Section ' . $sectionName : '' }}</span></div>
      @if($campusName)
      <div class="row"><span class="lbl">Campus</span><span class="val">{{ $campusName }}</span></div>
      @endif
      <div class="row"><span class="lbl">Academic Year</span><span class="val">{{ $academicYear }}</span></div>
    </div>

    <div class="highlight-box">
      ⏰ <strong>Time-sensitive:</strong> As a waitlist promotion, please complete your student profile and confirm your seat within <strong>3 days</strong>. If we do not hear back, the seat may be offered to the next applicant.
    </div>

    <p>Please click the button below to complete your student profile and confirm your admission:</p>

    <div class="btn-wrap">
      <a href="{{ $completeUrl }}" class="btn">✅ Confirm My Seat &amp; Complete Profile</a>
    </div>

    <p style="font-size:13px;color:#94a3b8;text-align:center;">Or paste this link in your browser:<br>
      <span style="color:#7c3aed;word-break:break-all;">{{ $completeUrl }}</span>
    </p>

    <p>If you have any questions or wish to decline, please contact the admissions office immediately so we can offer the seat to the next candidate on the waitlist.</p>
    <p>Warm regards,<br><strong>{{ $schoolName }}</strong><br>Admissions Office</p>
  </div>
  <div class="footer">
    <p>{{ $schoolName }} · Waitlist Promotion Notice</p>
    <p style="margin-top:6px;">You received this email because you were on the admission waitlist.</p>
  </div>
</div>
</body>
</html>
